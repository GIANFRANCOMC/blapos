<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Branches;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Assets\{AssetAssignment};
use App\Models\System\Organizations\{Branch};
use App\Services\System\Organizations\Branches\{SerieService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use App\Services\System\Warehouses\Warehouses\{WarehouseService};
use Exception;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing module operations
 * Handles business logic for creating and updating records
 */
class BranchService {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Organizations.branch";

    /**
     * Allowed fields for record creation and update
     */
    private const ALLOWED_FIELDS = [
        "internal_code",
        "name",
        "address",
        "reference",
        "telephone",
        "email",
        "capacity",
        "map_url",
        "status",
    ];

    /**
     * Searchable fields for filtering
     */
    private const SEARCHABLE_FIELDS = [
        "internal_code",
        "name",
        "address",
        "reference",
        "telephone",
        "email",
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
    private static function prepareBranchDataForCreate(array $data, int $userId): array {

        $branchData = [
            "company_id" => app(TenantCompanyContext::class)->id(),
            "status" => $data["status"] ?? "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(isset($data[$field])) {

                $branchData[$field] = $data[$field];

            }

        }

        return $branchData;

    }

    /**
     * Prepare data for update (only changed fields)
     *
     * @param  Branch  $branch Record instance
     * @param  array  $data Input data
     */
    private static function prepareBranchDataForUpdate(Branch $branch, array $data): array {

        $updateData = [];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(isset($data[$field])) {

                if($data[$field] !== $branch->$field) {

                    $updateData[$field] = $data[$field];

                }

            }

        }

        return $updateData;

    }

    /**
     * Create a new record
     *
     * @param  array  $data Input data
     * @param  int  $companyId Company that owns the branch
     * @param  int  $userId User creating the record
     * @return Branch|null Created record instance or null on failure
     *
     * @throws Exception
     */
    public static function create(array $data, int $userId): ?Branch {

        $branch = null;

        DB::transaction(function() use ($data, $userId, &$branch) {

            // Prepare data with only allowed fields
            $branchData = self::prepareBranchDataForCreate($data, $userId);

            // Create the record

            $branch = Branch::create($branchData);

            // Create related series for document types
            SerieService::createForBranch(
                $branch->id,
                $userId
            );

            // Create default warehouse
            WarehouseService::createDefaultForBranch($branch, $userId);

        });

        return $branch;

    }

    /**
     * Update an existing record
     *
     * @param  Branch  $branch Record instance to update
     * @param  array  $data Input data
     * @param  int  $userId User updating the record
     * @return Branch Updated record instance
     */
    public static function update(Branch $branch, array $data, int $userId): Branch {

        DB::transaction(function() use ($branch, $data, $userId) {

            // Prepare update data with only changed fields
            $updateData = self::prepareBranchDataForUpdate($branch, $data);

            $nameChanged = isset($updateData["name"]);

            if(($updateData["status"] ?? null) === "inactive"
                && AssetAssignment::query()
                    ->where("branch_id", $branch->id)
                    ->whereIn("status", ["active", "maintenance"])
                    ->exists()) {

                throw new Exception("No se puede inactivar la sucursal mientras tenga activos asignados a colaboradores.");

            }

            // Only update if there are changes

            if(!empty($updateData)) {

                $updateData["updated_at"] = now();
                $updateData["updated_by"] = $userId;
                $branch->update($updateData);

                // Update related warehouses names if branch name changed

                if($nameChanged) {

                    WarehouseService::updateNamesForBranch($branch->fresh(["warehousesAll"]), $userId);

                }

            }

        });

        return $branch->fresh();

    }

    /**
     * Find record by ID and company ID
     *
     * @param  int  $id Record
     * @param  int  $companyId Company
     * @param  array|null  $statuses Filter by statuses (e.g. ["active"], ["active", "inactive"])
     * @param  array  $relations Relations to eager load
     */
    public static function findByIdInTenant(int $id, ?array $statuses = ["active"], array $relations = ["series.documentType", "warehouses"]): ?Branch {

        $query = Branch::query()
            ->where("id", $id);

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
    public static function getPaginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator {

        $query = Branch::query()
            ->with(["series.documentType", "warehouses"]);

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
