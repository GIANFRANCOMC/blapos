<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Recipes;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item, RecipeDish, RecipeDishComponent, RecipeDishOption, RecipeDishOptionComponent, RecipeDishTopping, RecipeTopping, RecipeToppingComponent};
use App\Models\System\Warehouses\{Warehouse, WarehouseItem};
use DomainException;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

final class RecipeService {
    private const RELATIONS = [
        "item.brand",
        "item.currency",
        "components.item",
        "dishToppings.topping.currency",
        "dishToppings.topping.components.item",
        "options.components.item",
    ];

    public static function getPaginatedList(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator {

        return self::getFilteredListQuery($companyId, $filters)->paginate($perPage);

    }

    public static function getFilteredListQuery(int $companyId, array $filters = []): Builder {

        $query = RecipeDish::query()
            ->with(self::RELATIONS);

        $filterBy = $filters["filter_by"] ?? null;
        $word = $filters["word"] ?? null;

        if(Utilities::isDefined($word) && Utilities::isDefined($filterBy)) {

            $searchTerm = Utilities::getWordSearch($word);

            $query->where(function(Builder $builder) use ($filterBy, $searchTerm) {

                if($filterBy === "all") {

                    $builder->whereHas("item", function(Builder $itemQuery) use ($searchTerm) {

                        $itemQuery->where("name", "like", $searchTerm)
                            ->orWhere("internal_code", "like", $searchTerm)
                            ->orWhere("barcode", "like", $searchTerm)
                            ->orWhere("description", "like", $searchTerm);

                    })->orWhere("preparation_notes", "like", $searchTerm);

                    return;

                }

                if(in_array($filterBy, ["name", "internal_code", "barcode"], true)) {

                    $builder->whereHas("item", fn(Builder $itemQuery) => $itemQuery->where($filterBy, "like", $searchTerm));

                    return;

                }

                if($filterBy === "preparation_notes") {

                    $builder->where("preparation_notes", "like", $searchTerm);

                }

            });

        }

        return $query->orderByDesc("id");

    }

    public static function create(array $data, int $companyId, int $userId): RecipeDish {

        return DB::transaction(function() use ($data, $companyId, $userId) {

            self::assertItemExistsInTenant((int) $data["item_id"], $companyId);

            $recipe = RecipeDish::create([
                "item_id" => (int) $data["item_id"],
                "yield_quantity" => (float) ($data["yield_quantity"] ?? 1),
                "waste_percentage" => (float) ($data["waste_percentage"] ?? 0),
                "preparation_notes" => $data["preparation_notes"] ?? null,
                "status" => $data["status"] ?? "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            self::syncChildren($recipe, $data, $companyId, $userId);

            return $recipe->fresh(self::RELATIONS);

        });

    }

    public static function update(RecipeDish $recipe, array $data, int $companyId, int $userId): RecipeDish {

        return DB::transaction(function() use ($recipe, $data, $companyId, $userId) {

            self::assertItemExistsInTenant((int) $data["item_id"], $companyId);

            $recipe->update([
                "item_id" => (int) $data["item_id"],
                "yield_quantity" => (float) ($data["yield_quantity"] ?? 1),
                "waste_percentage" => (float) ($data["waste_percentage"] ?? 0),
                "preparation_notes" => $data["preparation_notes"] ?? null,
                "status" => $data["status"] ?? "active",
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);

            self::syncChildren($recipe, $data, $companyId, $userId);

            return $recipe->fresh(self::RELATIONS);

        });

    }

    public static function delete(RecipeDish $recipe, int $companyId): void {

        DB::transaction(function() use ($recipe) {

            $toppingIds = RecipeDishTopping::where("recipe_dish_id", $recipe->id)
                ->pluck("recipe_topping_id")
                ->all();

            $recipe->delete();

            if(!empty($toppingIds)) {

                RecipeTopping::whereIn("id", $toppingIds)->delete();

            }

        });

    }

    public static function theoreticalCost(
        int $recipeId,
        int $warehouseId,
        int $companyId,
        ?array $allowedWarehouseIds = null
    ): array {

        if($allowedWarehouseIds !== null && !in_array($warehouseId, $allowedWarehouseIds, true)) {

            throw new DomainException("No tienes acceso al almacén seleccionado.");

        }

        $warehouse = Warehouse::query()
            ->where("status", "active")
            ->find($warehouseId);

        if(!$warehouse) {

            throw new DomainException("El almacén seleccionado no está activo o no pertenece a la empresa.");

        }

        $recipe = RecipeDish::query()
            ->with(self::RELATIONS)
            ->find($recipeId);

        if(!$recipe) {

            throw new DomainException("La receta seleccionada no pertenece a la empresa.");

        }

        $itemIds = $recipe->components->pluck("item_id");

        foreach($recipe->options as $option) {

            $itemIds = $itemIds->merge($option->components->pluck("item_id"));

        }

        foreach($recipe->dishToppings as $link) {

            $itemIds = $itemIds->merge($link->topping?->components?->pluck("item_id") ?? collect());

        }

        $itemIds = $itemIds->filter()->unique()->values();

        $costs = WarehouseItem::query()
            ->where("warehouse_id", $warehouseId)
            ->whereIn("item_id", $itemIds)
            ->pluck("average_cost", "item_id");

        $yield = max(0.0001, (float) $recipe->yield_quantity);
        $recipeWasteFactor = 1 + (max(0, (float) $recipe->waste_percentage) / 100);
        $base = self::costComponents($recipe->components, $costs, $recipeWasteFactor / $yield, $companyId);
        $options = $recipe->options->map(fn($option) => [
            "id" => $option->id,
            "name" => $option->name,
            "cost" => self::costComponents($option->components, $costs, $recipeWasteFactor, $companyId)["total"],
        ])->values();

        $toppings = $recipe->dishToppings->map(fn($link) => [
            "id" => $link->id,
            "name" => $link->topping?->name,
            "cost" => self::costComponents($link->topping?->components ?? collect(), $costs, $recipeWasteFactor, $companyId)["total"],
        ])->values();

        return [
            "recipe_id" => $recipe->id,
            "item" => $recipe->item,
            "warehouse" => $warehouse,
            "yield_quantity" => Utilities::round($yield, null, $companyId),
            "base_components" => $base["components"],
            "base_cost" => $base["total"],
            "option_costs" => $options,
            "topping_costs" => $toppings,
            "missing_cost_item_ids" => $itemIds
                ->reject(fn($itemId) => $costs->has($itemId) && $costs->get($itemId) !== null)
                ->values()
                ->all(),
        ];

    }

    private static function syncChildren(RecipeDish $recipe, array $data, int $companyId, int $userId): void {

        self::syncComponents($recipe, $data["components"] ?? [], $companyId, $userId);
        self::syncToppings($recipe, $data["toppings"] ?? [], $companyId, $userId);
        self::syncOptions($recipe, $data["options"] ?? [], $companyId, $userId);

    }

    private static function syncComponents(RecipeDish $recipe, array $components, int $companyId, int $userId): void {

        RecipeDishComponent::where("recipe_dish_id", $recipe->id)->delete();

        foreach($components as $component) {

            $itemId = (int) ($component["item_id"] ?? 0);
            $quantity = (float) ($component["quantity"] ?? 0);

            if($itemId <= 0 || $quantity <= 0) {

                continue;

            }

            self::assertItemExistsInTenant($itemId, $companyId);

            RecipeDishComponent::create([
                "recipe_dish_id" => $recipe->id,
                "item_id" => $itemId,
                "quantity" => $quantity,
                "waste_percentage" => (float) ($component["waste_percentage"] ?? 0),
                "note" => $component["note"] ?? null,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

        }

    }

    private static function syncToppings(RecipeDish $recipe, array $toppings, int $companyId, int $userId): void {

        $recipe->loadMissing("item");

        $oldToppingIds = RecipeDishTopping::where("recipe_dish_id", $recipe->id)
            ->pluck("recipe_topping_id")
            ->all();

        RecipeDishTopping::where("recipe_dish_id", $recipe->id)->delete();

        if(!empty($oldToppingIds)) {

            RecipeTopping::whereIn("id", $oldToppingIds)->delete();

        }

        foreach($toppings as $toppingData) {

            $name = trim((string) ($toppingData["name"] ?? ""));

            if($name === "") {

                continue;

            }

            $topping = RecipeTopping::create([
                "currency_id" => (int) ($toppingData["currency_id"] ?? $recipe->item?->currency_id),
                "item_id" => $toppingData["item_id"] ?? null,
                "name" => $name,
                "description" => $toppingData["description"] ?? null,
                "price" => (float) ($toppingData["price"] ?? 0),
                "max_quantity" => $toppingData["max_quantity"] ?? null,
                "status" => $toppingData["status"] ?? "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            RecipeDishTopping::create([
                "recipe_dish_id" => $recipe->id,
                "recipe_topping_id" => $topping->id,
                "is_default" => (bool) ($toppingData["is_default"] ?? false),
                "min_quantity" => (int) ($toppingData["min_quantity"] ?? 0),
                "max_quantity" => $toppingData["max_quantity"] ?? null,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            self::syncToppingComponents($topping, $toppingData["components"] ?? [], $companyId, $userId);

        }

    }

    private static function syncToppingComponents(RecipeTopping $topping, array $components, int $companyId, int $userId): void {

        foreach($components as $component) {

            $itemId = (int) ($component["item_id"] ?? 0);
            $quantity = (float) ($component["quantity"] ?? 0);

            if($itemId <= 0 || $quantity <= 0) {

                continue;

            }

            self::assertItemExistsInTenant($itemId, $companyId);

            RecipeToppingComponent::create([
                "recipe_topping_id" => $topping->id,
                "item_id" => $itemId,
                "quantity" => $quantity,
                "waste_percentage" => (float) ($component["waste_percentage"] ?? 0),
                "note" => $component["note"] ?? null,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

        }

    }

    private static function syncOptions(RecipeDish $recipe, array $options, int $companyId, int $userId): void {

        RecipeDishOption::where("recipe_dish_id", $recipe->id)->delete();

        foreach($options as $optionData) {

            $name = trim((string) ($optionData["name"] ?? ""));

            if($name === "") {

                continue;

            }

            $option = RecipeDishOption::create([
                "recipe_dish_id" => $recipe->id,
                "name" => $name,
                "description" => $optionData["description"] ?? null,
                "max_portions" => $optionData["max_portions"] ?? null,
                "status" => $optionData["status"] ?? "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            foreach(($optionData["components"] ?? []) as $component) {

                $itemId = (int) ($component["item_id"] ?? 0);
                $quantity = (float) ($component["quantity"] ?? 0);

                if($itemId <= 0 || $quantity <= 0) {

                    continue;

                }

                self::assertItemExistsInTenant($itemId, $companyId);

                RecipeDishOptionComponent::create([
                    "recipe_dish_option_id" => $option->id,
                    "item_id" => $itemId,
                    "quantity" => $quantity,
                    "waste_percentage" => (float) ($component["waste_percentage"] ?? 0),
                    "note" => $component["note"] ?? null,
                    "status" => "active",
                    "created_at" => now(),
                    "created_by" => $userId,
                ]);

            }

        }

    }

    private static function assertItemExistsInTenant(int $itemId, int $companyId): void {

        if($itemId <= 0 || !Item::whereKey($itemId)->exists()) {

            throw new DomainException("El producto, servicio o insumo seleccionado no pertenece a la empresa.");

        }

    }

    private static function costComponents($components, $costs, float $multiplier, int $companyId): array {

        $rows = collect($components)->map(function($component) use ($costs, $multiplier, $companyId) {

            $wasteFactor = 1 + (max(0, (float) $component->waste_percentage) / 100);
            $quantity = Utilities::round((float) $component->quantity * $multiplier * $wasteFactor, null, $companyId);
            $unitCost = Utilities::round((float) ($costs->get($component->item_id) ?? 0), null, $companyId);

            return [
                "item_id" => (int) $component->item_id,
                "item" => $component->item,
                "quantity_with_waste" => $quantity,
                "average_unit_cost" => $unitCost,
                "cost" => Utilities::round($quantity * $unitCost, null, $companyId),
            ];

        })->values();

        return [
            "components" => $rows,
            "total" => Utilities::round((float) $rows->sum("cost"), null, $companyId),
        ];

    }
}
