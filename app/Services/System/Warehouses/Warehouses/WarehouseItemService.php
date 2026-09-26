<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\Warehouses;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Warehouses\{Warehouse, WarehouseItem};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};

class WarehouseItemService {
    public static function syncProductInventory(
        int $itemId,
        array $inventory,
        ?int $userId = null,
        bool $setInitialStock = false
    ): void {

        $inventoryByWarehouse = collect($inventory)->keyBy(
            fn(array $record) => (int) ($record["warehouse_id"] ?? 0)
        );

        $warehouses = Warehouse::where("status", "active")
            ->whereHas("branch", function($query) {

                $query->where("status", "active");

            })
            ->get();

        foreach($warehouses as $warehouse) {

            $inventoryRecord = $inventoryByWarehouse->get((int) $warehouse->id, []);
            $warehouseItem = WarehouseItem::firstOrNew([
                "warehouse_id" => $warehouse->id,
                "item_id" => $itemId,
            ]);

            $isNew = !$warehouseItem->exists;

            if($isNew) {

                $warehouseItem->quantity = 0;
                $warehouseItem->created_at = now();
                $warehouseItem->created_by = $userId;

            }

            $warehouseItem->minimum_stock = (float) (
                $inventoryRecord["minimum_stock"] ?? $warehouseItem->minimum_stock ?? 0
            );

            $warehouseItem->status = "active";

            if(!$isNew) {

                $warehouseItem->updated_at = now();
                $warehouseItem->updated_by = $userId;

            }

            $warehouseItem->save();

            $initialStock = Utilities::round((float) ($inventoryRecord["initial_stock"] ?? 0));

            if($isNew && $setInitialStock && $initialStock > 0) {

                InventoryMovementService::apply([
                    "warehouse_id" => (int) $warehouse->id,
                    "item_id" => $itemId,
                    "user_id" => $userId,
                    "movement_type" => InventoryMovementService::TYPE_ENTRY,
                    "origin_type" => InventoryMovementService::ORIGIN_PRODUCT_OPENING,
                    "origin_id" => $itemId,
                    "quantity" => $initialStock,
                    "reason" => "Stock inicial registrado al crear el producto.",
                ]);

            }

        }

    }

    public static function createForWarehouse(int $warehouseId, ?int $userId = null): void {

        $productIds = Item::query()
            ->where("type", "product")
            ->pluck("id");

        foreach($productIds as $itemId) {

            WarehouseItem::firstOrCreate(
                [
                    "warehouse_id" => $warehouseId,
                    "item_id" => $itemId,
                ],
                [
                    "quantity" => 0,
                    "minimum_stock" => 0,
                    "status" => "active",
                    "created_at" => now(),
                    "created_by" => $userId,
                ]
            );

        }

    }
}
