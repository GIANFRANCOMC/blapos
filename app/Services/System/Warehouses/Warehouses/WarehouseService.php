<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\Warehouses;

use App\Models\System\Organizations\{Branch};
use App\Models\System\Warehouses\{Warehouse};
use App\Services\System\Tenancy\{TenantCompanyContext};

/**
 * Service class for managing Warehouse operations
 * Handles business logic for creating and updating warehouses related to branches
 */
class WarehouseService {
    /**
     * Create default warehouse for a new branch
     *
     * @param  Branch  $branch Persisted branch that owns the warehouse
     * @param  int|null  $userId User ID creating the warehouse
     * @return Warehouse Created warehouse instance
     */
    public static function createDefaultForBranch(Branch $branch, ?int $userId = null): Warehouse {

        $warehouse = Warehouse::create([
            "branch_id" => $branch->id,
            "name" => self::generateWarehouseName(1),
            "status" => "active",
            "created_at" => now(),
            "created_by" => $userId,
        ]);

        WarehouseItemService::createForWarehouse(
            (int) $warehouse->id,
            app(TenantCompanyContext::class)->id(),
            $userId
        );

        return $warehouse;

    }

    /**
     * Update warehouse names for a branch when branch name changes
     * Uses bulk update for better performance
     *
     * @param  Branch  $branch Branch instance
     * @param  int|null  $userId User ID updating the warehouses
     * @return int Number of updated warehouses
     */
    public static function updateNamesForBranch(Branch $branch, ?int $userId = null): int {

        $warehouses = $branch->warehousesAll;

        if($warehouses->isEmpty()) {

            return 0;

        }

        $now = now();

        $seq = 1;

        // Update warehouses efficiently

        foreach($warehouses as $warehouse) {

            $cleanName = Warehouse::plainName((string) $warehouse->name);

            $warehouse->name = $cleanName !== "" ? $cleanName : self::generateWarehouseName($seq);
            $warehouse->updated_at = $now;
            $warehouse->updated_by = $userId;
            $warehouse->save();

            $seq++;

        }

        return $warehouses->count();

    }

    /**
     * Generate warehouse name based on branch name and sequence
     *
     * @param  string  $branchName Branch name
     * @param  int  $sequence Sequence number
     * @return string Generated warehouse name
     */
    public static function generateWarehouseName(int $sequence): string {

        return "Almacén {$sequence}";

    }
}
