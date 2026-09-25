<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Subscriptions;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Catalogs\{Item};
use App\Services\System\Catalogs\Categories\{CategoryItemService};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing module operations
 * Handles business logic for creating and updating records
 */
class SubscriptionService {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Catalogs.subscription";

    /**
     * Allowed fields for record creation and update
     */
    private const ALLOWED_FIELDS = [
        "internal_code",
        "name",
        "description",
        "price",
        "price_includes_tax",
        "igv_exempt",
        "min_price",
        "max_price",
        "commission_type",
        "commission_value",
        "currency_id",
        "duration_type",
        "duration_value",
        "capacity_control_enabled",
        "capacity_limit",
        "attendance_limit_per_day",
        "expires_at",
        "benefits",
        "restrictions",
        "see_my_web",
        "see_my_web_price",
        "status",
    ];

    /**
     * Searchable fields for filtering
     */
    private const SEARCHABLE_FIELDS = [
        "internal_code",
        "name",
        "description",
        "price",
    ];

    /**
     * Get translation with fallback
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    private static function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback(self::TRANSLATION_NAMESPACE, $key, $replace);

    }

    /**
     * Prepare data for creation
     *
     * @param  array  $data Input data
     * @param  int  $companyId Company
     * @param  int  $userId User
     */
    private static function prepareSubscriptionDataForCreate(array $data, int $companyId, int $userId): array {

        $itemData = [
            "type" => "subscription",
            "brand_id" => null,
            "barcode" => null,
            "capacity_used" => 0,
            "status" => $data["status"] ?? "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(array_key_exists($field, $data)) {

                if(in_array($field, ["min_price", "max_price"])) {

                    $itemData[$field] = floatval($data[$field]) <= 0 ? null : $data[$field];

                }elseif($field === "see_my_web_price") {

                    $itemData[$field] = ($data["see_my_web"] ?? false) ? ($data[$field] ?? false) : false;

                }else {

                    $itemData[$field] = $data[$field];

                }

            }

        }

        if((bool) ($itemData["igv_exempt"] ?? false)) {

            $itemData["price_includes_tax"] = false;

        }

        return self::normalizeCapacity($itemData);

    }

    /**
     * Prepare data for update (only changed fields)
     *
     * @param  Item  $item Record instance
     * @param  array  $data Input data
     */
    private static function prepareSubscriptionDataForUpdate(Item $item, array $data): array {

        $updateData = [];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(array_key_exists($field, $data)) {

                if(in_array($field, ["min_price", "max_price"])) {

                    $value = floatval($data[$field]) <= 0 ? null : $data[$field];

                    if($value !== $item->$field) {

                        $updateData[$field] = $value;

                    }

                }elseif($field === "see_my_web_price") {

                    $value = ($data["see_my_web"] ?? $item->see_my_web) ? ($data[$field] ?? false) : false;

                    if($value !== $item->$field) {

                        $updateData[$field] = $value;

                    }

                }elseif($data[$field] !== $item->$field) {

                    $updateData[$field] = $data[$field];

                }

            }

        }

        if($item->brand_id !== null) {

            $updateData["brand_id"] = null;

        }

        if($item->barcode !== null) {

            $updateData["barcode"] = null;

        }

        if((bool) ($updateData["igv_exempt"] ?? $item->igv_exempt ?? false)) {

            $updateData["price_includes_tax"] = false;

        }

        $updateData = self::normalizeCapacity($updateData);

        $capacityEnabled = array_key_exists("capacity_control_enabled", $updateData)
            ? (bool) $updateData["capacity_control_enabled"]
            : (bool) $item->capacity_control_enabled;

        $capacityLimit = array_key_exists("capacity_limit", $updateData)
            ? (int) ($updateData["capacity_limit"] ?? 0)
            : (int) ($item->capacity_limit ?? 0);

        if($capacityEnabled && $capacityLimit < (int) $item->capacity_used) {

            throw new \InvalidArgumentException("El límite de cupos no puede ser menor que los cupos ya vendidos.");

        }

        return $updateData;

    }

    private static function normalizeCapacity(array $itemData): array {

        if(!array_key_exists("capacity_control_enabled", $itemData)) {

            return $itemData;

        }

        $enabled = (bool) $itemData["capacity_control_enabled"];

        $itemData["capacity_control_enabled"] = $enabled;

        if(!$enabled) {

            $itemData["capacity_limit"] = null;
            $itemData["capacity_used"] = 0;

            return $itemData;

        }

        $itemData["capacity_limit"] = max(1, (int) ($itemData["capacity_limit"] ?? 1));

        return $itemData;

    }

    /**
     * Create a new record
     *
     * @param  array  $data Input data
     * @param  int|null  $userId User creating the record
     * @return Item|null Created record instance or null on failure
     *
     * @throws Exception
     */
    public static function create(array $data, int $companyId, int $userId): ?Item {

        $item = null;

        DB::transaction(function() use ($data, $companyId, $userId, &$item) {

            // Prepare data with only allowed fields
            $itemData = self::prepareSubscriptionDataForCreate($data, $companyId, $userId);

            // Create the record

            $item = Item::create($itemData);

            // Sync categories

            if(isset($data["categories"]) && is_array($data["categories"])) {

                CategoryItemService::sync($item->id, $data["categories"], $userId);

            }

        });

        return $item;

    }

    /**
     * Update an existing record
     *
     * @param  Item  $item Record instance to update
     * @param  array  $data Input data
     * @param  int|null  $userId User updating the record
     * @return Item Updated record instance
     */
    public static function update(Item $item, array $data, int $userId): Item {

        DB::transaction(function() use ($item, $data, $userId) {

            // Prepare update data with only changed fields
            $updateData = self::prepareSubscriptionDataForUpdate($item, $data);

            // Only update if there are changes

            if(!empty($updateData)) {

                $updateData["updated_at"] = now();
                $updateData["updated_by"] = $userId;
                $item->update($updateData);

            }

            // Sync categories

            if(isset($data["categories"]) && is_array($data["categories"])) {

                CategoryItemService::sync($item->id, $data["categories"], $userId);

            }

        });

        return $item->fresh(["currency", "categoryItems"]);

    }

    /**
     * Find record by ID and company ID
     *
     * @param  int  $id Record
     * @param  int  $companyId Company
     * @param  array|null  $statuses Filter by statuses (e.g. ["active"], ["active", "inactive"])
     * @param  array  $relations Relations to eager load
     */
    public static function findByIdInTenant(int $id, int $companyId, ?array $statuses = ["active"], array $relations = ["currency", "categoryItems"]): ?Item {

        $query = Item::where("id", $id)
            ->where("type", "subscription");

        if($statuses !== null && !empty($statuses)) {

            $query->whereIn("status", $statuses);

        }

        if($relations !== null && !empty($relations)) {

            $query->with($relations);

        }

        return $query->first();

    }

    /**
     * Get paginated list of records with filters
     *
     * @param  int  $companyId Company
     * @param  array  $filters Filter parameters (filter_by, word)
     * @param  int  $perPage Items per page
     */
    public static function getPaginatedList(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator {

        Item::expireActiveItems();

        $query = Item::query()
            ->where("type", "subscription")
            ->with(["currency", "categoryItems"]);

        // Apply filters

        $filterBy = $filters["filter_by"] ?? null;

        $word = $filters["word"] ?? null;

        if(Utilities::isDefined($word) && Utilities::isDefined($filterBy)) {

            $searchTerm = Utilities::getWordSearch($word);

            if($filterBy === "all") {

                // Search across all searchable fields
                $query->where(function(Builder $q) use ($searchTerm) {

                    $searchableFields = self::SEARCHABLE_FIELDS;
                    $firstField = array_shift($searchableFields);

                    if($firstField) {

                        $q->where($firstField, "like", $searchTerm);

                    }

                    foreach($searchableFields as $field) {

                        $q->orWhere($field, "like", $searchTerm);

                    }

                });

            }elseif(in_array($filterBy, self::SEARCHABLE_FIELDS, true)) {

                // Search in specific field
                $query->where($filterBy, "like", $searchTerm);

            }

        }

        return $query->orderBy("name", "ASC")
            ->paginate($perPage);

    }
}
