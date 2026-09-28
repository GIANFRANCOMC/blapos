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
            ->select("id")
            ->whereHas("branch", function($query) {

                $query->where("status", "active");

            })
            ->get();

        if($warehouses->isEmpty()) {

            return;

        }

        $warehouseIds = $warehouses->pluck("id")->map(fn($id) => (int) $id);

        $existingInventory = WarehouseItem::query()
            ->where("item_id", $itemId)
            ->whereIn("warehouse_id", $warehouseIds->all())
            ->get()
            ->keyBy(fn(WarehouseItem $warehouseItem) => (int) $warehouseItem->warehouse_id);

        $newWarehouseIds = $warehouseIds
            ->reject(fn(int $warehouseId): bool => $existingInventory->has($warehouseId));

        $timestamp = now();
        $records = $warehouseIds
            ->map(function(int $warehouseId) use (
                $existingInventory,
                $inventoryByWarehouse,
                $itemId,
                $timestamp,
                $userId
            ): array {

                $current = $existingInventory->get($warehouseId);
                $inventoryRecord = $inventoryByWarehouse->get($warehouseId, []);
                $isNew = $current === null;

                return [
                    "warehouse_id" => $warehouseId,
                    "item_id" => $itemId,
                    "quantity" => 0,
                    "minimum_stock" => (float) (
                        $inventoryRecord["minimum_stock"] ?? $current?->minimum_stock ?? 0
                    ),
                    "status" => "active",
                    "created_at" => $timestamp,
                    "created_by" => $userId,
                    "updated_at" => $isNew ? null : $timestamp,
                    "updated_by" => $isNew ? null : $userId,
                ];

            })
            ->all();

        WarehouseItem::query()->upsert(
            $records,
            ["warehouse_id", "item_id"],
            ["minimum_stock", "status", "updated_at", "updated_by"]
        );

        if(!$setInitialStock) {

            return;

        }

        foreach($newWarehouseIds as $warehouseId) {

            $inventoryRecord = $inventoryByWarehouse->get($warehouseId, []);
            $initialStock = Utilities::round((float) ($inventoryRecord["initial_stock"] ?? 0));

            if($initialStock <= 0) {

                continue;

            }

            InventoryMovementService::apply([
                "warehouse_id" => $warehouseId,
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

    public static function createForWarehouse(int $warehouseId, ?int $userId = null): void {

        $timestamp = now();

        Item::query()
            ->select("id")
            ->where("type", "product")
            ->chunkById(500, function($products) use ($timestamp, $userId, $warehouseId): void {

                $records = $products
                    ->map(fn(Item $item): array => [
                        "warehouse_id" => $warehouseId,
                        "item_id" => (int) $item->id,
                        "quantity" => 0,
                        "minimum_stock" => 0,
                        "status" => "active",
                        "created_at" => $timestamp,
                        "created_by" => $userId,
                    ])
                    ->all();

                WarehouseItem::query()->insertOrIgnore($records);

            });

    }
}
