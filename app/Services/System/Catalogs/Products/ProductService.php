<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Products;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Catalogs\{Brand, Item};
use App\Services\System\Catalogs\Categories\{CategoryItemService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use App\Services\System\Warehouses\Warehouses\{WarehouseItemService};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing module operations
 * Handles business logic for creating and updating records
 */
class ProductService {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Catalogs.product";

    /**
     * Allowed fields for record creation and update
     */
    private const ALLOWED_FIELDS = [
        "internal_code",
        "barcode",
        "brand_id",
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
        "capacity_control_enabled",
        "capacity_limit",
        "expires_at",
        "see_my_web",
        "see_my_web_price",
        "status",
    ];

    /**
     * Searchable fields for filtering
     */
    private const SEARCHABLE_FIELDS = [
        "internal_code",
        "barcode",
        "name",
        "description",
        "price",
    ];

    private static function hasInputField(array $data, string $field): bool {

        return array_key_exists($field, $data);

    }

    private static function normalizeOptionalPrice(mixed $value): ?float {

        if($value === null || $value === "" || (is_numeric($value) && (float) $value <= 0)) {

            return null;

        }

        return (float) $value;

    }

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
    private static function prepareProductDataForCreate(array $data, int $companyId, int $userId): array {

        $itemData = [
            "type" => "product",
            "status" => $data["status"] ?? "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(self::hasInputField($data, $field)) {

                if(in_array($field, ["min_price", "max_price"])) {

                    $itemData[$field] = self::normalizeOptionalPrice($data[$field]);

                }elseif($field === "capacity_control_enabled") {

                    $enabled = (bool) $data[$field];
                    $itemData["capacity_control_enabled"] = $enabled;
                    $itemData["capacity_limit"] = $enabled ? max(1, (int) ($data["capacity_limit"] ?? 1)) : null;
                    $itemData["capacity_used"] = $enabled ? (int) ($itemData["capacity_used"] ?? 0) : 0;

                }elseif($field === "capacity_limit") {

                    if(($itemData["capacity_control_enabled"] ?? false) === true) {

                        $itemData[$field] = max(1, (int) $data[$field]);

                    }

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

        return $itemData;

    }

    /**
     * Prepare data for update (only changed fields)
     *
     * @param  Item  $item Record instance
     * @param  array  $data Input data
     */
    private static function prepareProductDataForUpdate(Item $item, array $data): array {

        $updateData = [];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(self::hasInputField($data, $field)) {

                if(in_array($field, ["min_price", "max_price"])) {

                    $value = self::normalizeOptionalPrice($data[$field]);

                    if($value !== ($item->$field === null ? null : (float) $item->$field)) {

                        $updateData[$field] = $value;

                    }

                }elseif($field === "capacity_control_enabled") {

                    $enabled = (bool) $data[$field];

                    if($enabled !== (bool) $item->capacity_control_enabled) {

                        $updateData["capacity_control_enabled"] = $enabled;

                    }

                    $limit = $enabled ? max(1, (int) ($data["capacity_limit"] ?? 1)) : null;

                    if($limit !== ($item->capacity_limit === null ? null : (int) $item->capacity_limit)) {

                        $updateData["capacity_limit"] = $limit;

                    }

                    if(!$enabled && (int) $item->capacity_used !== 0) {

                        $updateData["capacity_used"] = 0;

                    }

                }elseif($field === "capacity_limit") {

                    if(($updateData["capacity_control_enabled"] ?? (bool) $item->capacity_control_enabled) === true) {

                        $value = max(1, (int) $data[$field]);

                        if($value !== (int) ($item->capacity_limit ?? 0)) {

                            $updateData[$field] = $value;

                        }

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

        if((bool) ($updateData["igv_exempt"] ?? $item->igv_exempt ?? false)) {

            $updateData["price_includes_tax"] = false;

        }

        return $updateData;

    }

    /**
     * Create a new record
     *
     * @param  array  $data Input data
     * @param  int  $companyId Company that owns the product
     * @param  int  $userId User creating the record
     * @return Item|null Created record instance or null on failure
     *
     * @throws Exception
     */
    public static function create(array $data, int $companyId, int $userId): ?Item {

        $item = null;

        DB::transaction(function() use ($data, $companyId, $userId, &$item) {

            self::assertBrandCanBeAssigned($data["brand_id"] ?? null, $companyId);

            // Prepare data with only allowed fields
            $itemData = self::prepareProductDataForCreate($data, $companyId, $userId);

            // Create the record

            $item = Item::create($itemData);

            // Create warehouse items for products
            WarehouseItemService::syncProductInventory(
                $item->id,
                $companyId,
                $data["inventory"] ?? [],
                $userId,
                true
            );

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
     * @param  int  $userId User updating the record
     * @return Item Updated record instance
     */
    public static function update(Item $item, array $data, int $userId): Item {

        DB::transaction(function() use ($item, $data, $userId) {

            $companyId = app(TenantCompanyContext::class)->id();

            self::assertBrandCanBeAssigned(
                $data["brand_id"] ?? null,
                $companyId,
                $item
            );

            // Prepare update data with only changed fields

            $updateData = self::prepareProductDataForUpdate($item, $data);

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

            WarehouseItemService::syncProductInventory(
                $item->id,
                $companyId,
                $data["inventory"] ?? [],
                $userId,
                false
            );

        });

        return $item->fresh(["brand", "currency", "categoryItems", "warehouseItems.warehouse.branch"]);

    }

    /**
     * Find record by ID and company ID
     *
     * @param  int  $id Record
     * @param  int  $companyId Company
     * @param  array|null  $statuses Filter by statuses (e.g. ["active"], ["active", "inactive"])
     * @param  array  $relations Relations to eager load
     */
    public static function findByIdInTenant(int $id, int $companyId, ?array $statuses = ["active"], array $relations = ["brand", "currency", "categoryItems", "warehouseItems.warehouse.branch"]): ?Item {

        $query = Item::where("id", $id)
            ->where("type", "product");

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

        Item::expireActiveItems($companyId);

        return self::getFilteredListQuery($companyId, $filters)
            ->paginate($perPage);

    }

    /**
     * Build the shared Products listing query.
     *
     * Pagination and exports must use this method so filters, relationships
     * and ordering cannot drift between the screen and downloaded reports.
     *
     * @param  int  $companyId Company
     * @param  array  $filters Filter parameters (filter_by, word)
     */
    public static function getFilteredListQuery(int $companyId, array $filters = []): Builder {

        $query = Item::query()
            ->where("type", "product")
            ->with([
                "brand",
                "currency",
                "categoryItems.category",
                "warehouseItems.warehouse.branch",
            ]);

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

                    $q->orWhereHas("brand", function(Builder $brandQuery) use ($searchTerm) {

                        $brandQuery->where("name", "like", $searchTerm);

                    });

                });

            }elseif($filterBy === "brand") {

                $query->whereHas("brand", function(Builder $brandQuery) use ($searchTerm) {

                    $brandQuery->where("name", "like", $searchTerm);

                });

            }elseif(in_array($filterBy, self::SEARCHABLE_FIELDS, true)) {

                // Search in specific field
                $query->where($filterBy, "like", $searchTerm);

            }

        }

        return $query->orderBy("name", "ASC");

    }

    private static function assertBrandCanBeAssigned(?int $brandId, int $companyId, ?Item $currentItem = null): void {

        if(!$brandId) {

            return;

        }

        $brand = Brand::query()
            ->whereKey($brandId)
            ->first();

        $keepsCurrentInactiveBrand = $brand
            && $brand->status === "inactive"
            && (int) ($currentItem?->brand_id ?? 0) === (int) $brand->id;

        if(!$brand || ($brand->status !== "active" && !$keepsCurrentInactiveBrand)) {

            throw new \InvalidArgumentException("La marca seleccionada no está disponible para la empresa.");

        }

    }
}
