<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Recipes;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item, RecipeDish, RecipeWasteRecord};
use App\Models\System\Warehouses\{Warehouse, WarehouseItem};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use Carbon\{Carbon};
use DomainException;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};

final class RecipeWasteService {
    public static function list(
        array $filters,
        int $perPage,
        ?array $allowedWarehouseIds = null
    ): LengthAwarePaginator {

        return RecipeWasteRecord::query()
            ->with(["recipe.item", "warehouse.branch", "item", "inventoryMovement", "createdBy"])
            ->when($allowedWarehouseIds !== null, fn($query) => $query->whereIn("warehouse_id", $allowedWarehouseIds))
            ->when($filters["recipe_dish_id"] ?? null, fn($query, $id) => $query->where("recipe_dish_id", $id))
            ->when($filters["warehouse_id"] ?? null, fn($query, $id) => $query->where("warehouse_id", $id))
            ->when($filters["item_id"] ?? null, fn($query, $id) => $query->where("item_id", $id))
            ->when($filters["date_from"] ?? null, fn($query, $date) => $query->where("occurred_at", ">=", Utilities::startOfDay($date)))
            ->when($filters["date_to"] ?? null, fn($query, $date) => $query->where("occurred_at", "<=", Utilities::endOfDay($date)))
            ->latest("occurred_at")
            ->paginate($perPage);

    }

    public static function register(
        int $recipeId,
        int $userId,
        array $data,
        ?array $allowedWarehouseIds = null
    ): RecipeWasteRecord {

        return DB::transaction(function() use ($recipeId, $userId, $data, $allowedWarehouseIds) {

            $warehouseId = (int) $data["warehouse_id"];
            $itemId = (int) $data["item_id"];

            if($allowedWarehouseIds !== null && !in_array($warehouseId, $allowedWarehouseIds, true)) {

                throw new DomainException("No tienes acceso al almacén seleccionado.");

            }

            $recipe = RecipeDish::query()->find($recipeId);

            $warehouse = Warehouse::query()->where("status", "active")->find($warehouseId);
            $item = Item::query()->where("type", "product")->find($itemId);

            if(!$recipe || !$warehouse || !$item) {

                throw new DomainException("La receta, el almacén o el insumo no pertenece a la empresa.");

            }

            $quantity = Utilities::round((float) $data["quantity"]);

            $unitCost = round((float) (WarehouseItem::query()
                ->where("warehouse_id", $warehouseId)
                ->where("item_id", $itemId)
                ->value("average_cost") ?? 0), 4);

            $movement = InventoryMovementService::apply([
                "warehouse_id" => $warehouseId,
                "item_id" => $itemId,
                "user_id" => $userId,
                "movement_type" => InventoryMovementService::TYPE_EXIT,
                "origin_type" => InventoryMovementService::ORIGIN_RECIPE_WASTE,
                "origin_id" => $recipe->id,
                "quantity" => $quantity,
                "unit_cost" => $unitCost,
                "reason" => trim((string) $data["reason"]),
                "allow_negative" => (bool) ($data["allow_negative"] ?? false),
                "metadata" => ["recipe_dish_id" => $recipe->id],
            ]);

            return RecipeWasteRecord::create([
                "recipe_dish_id" => $recipe->id,
                "warehouse_id" => $warehouseId,
                "item_id" => $itemId,
                "inventory_movement_id" => $movement->id,
                "quantity" => $quantity,
                "unit_cost" => $unitCost,
                "total_cost" => Utilities::round($quantity * $unitCost),
                "reason" => trim((string) $data["reason"]),
                "occurred_at" => Carbon::parse($data["occurred_at"] ?? now()),
                "created_at" => now(),
                "created_by" => $userId,
            ])->load(["recipe.item", "warehouse.branch", "item", "inventoryMovement", "createdBy"]);

        });

    }
}
