<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Recipes;

use App\Helpers\System\{Numbers};
use App\Models\System\Catalogs\{RecipeDish};
use App\Models\System\Sales\{SaleBody};
use App\Models\System\Warehouses\{Warehouse};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use DomainException;

final class RecipeConsumptionService {

    public static function consume(
        Warehouse $warehouse,
        SaleBody $saleBody,
        array $detail,
        int $userId,
        bool $allowNegative
    ): bool {

        $recipe = RecipeDish::query()
            ->where("item_id", $saleBody->item_id)
            ->where("status", "active")
            ->with([
                "components",
                "dishToppings.topping.components",
                "options.components",
            ])
            ->first();

        if(!$recipe) {

            return false;

        }

        $yield = max(Numbers::decimalStep(), (float) $recipe->yield_quantity);

        $portions = max(0, (float) $saleBody->quantity);
        $recipeWasteFactor = Numbers::sum([100, max(0, (float) $recipe->waste_percentage)]);
        $requirements = [];

        self::appendComponents($requirements, $recipe->components, [$portions, $recipeWasteFactor], $yield);

        $extras = is_array($detail["extras"] ?? null) ? $detail["extras"] : [];

        foreach(($extras["recipe_options"] ?? []) as $selected) {

            $optionId = (int) ($selected["option_id"] ?? 0);
            $option = $recipe->options->firstWhere("id", $optionId);

            if(!$option) {

                throw new DomainException("Una opción seleccionada no pertenece a la receta.");

            }

            $selectedPortions = max(1, (int) ($selected["portions"] ?? 1));

            if($option->max_portions !== null && $selectedPortions > (int) $option->max_portions) {

                throw new DomainException("La cantidad elegida para {$option->name} supera el máximo permitido.");

            }

            self::appendComponents($requirements, $option->components, [$portions, $selectedPortions, $recipeWasteFactor]);

        }

        foreach(($extras["recipe_toppings"] ?? []) as $selected) {

            $linkId = (int) ($selected["recipe_dish_topping_id"] ?? 0);
            $link = $recipe->dishToppings->firstWhere("id", $linkId);

            if(!$link) {

                throw new DomainException("Un extra seleccionado no pertenece a la receta.");

            }

            $quantity = max(0, (int) ($selected["quantity"] ?? 0));

            if($quantity < (int) $link->min_quantity
                || ($link->max_quantity !== null && $quantity > (int) $link->max_quantity)) {

                throw new DomainException("La cantidad elegida para {$link->topping->name} no está permitida.");

            }

            self::appendComponents($requirements, $link->topping->components, [$portions, $quantity, $recipeWasteFactor]);

        }

        foreach($requirements as $itemId => $amounts) {

            $quantity = Numbers::sum($amounts);

            if($quantity === 0.0) {

                continue;

            }

            InventoryMovementService::apply([
                "warehouse_id" => $warehouse->id,
                "item_id" => $itemId,
                "user_id" => $userId,
                "movement_type" => InventoryMovementService::TYPE_EXIT,
                "origin_type" => InventoryMovementService::ORIGIN_RECIPE_SALE,
                "origin_id" => $saleBody->id,
                "quantity" => $quantity,
                "reason" => "Consumo de insumos por venta de receta.",
                "allow_negative" => $allowNegative,
                "metadata" => [
                    "sale_header_id" => $saleBody->sale_header_id,
                    "recipe_dish_id" => $recipe->id,
                ],
            ]);

        }

        return true;

    }

    private static function appendComponents(array &$requirements, $components, array $factors, float $yield = 1): void {

        $divisor = Numbers::multiply($yield, 10000);

        foreach($components as $component) {

            $wasteFactor = Numbers::sum([100, max(0, (float) $component->waste_percentage)]);
            $requirements[(int) $component->item_id][] = Numbers::ratioOf([...$factors, $component->quantity, $wasteFactor], $divisor);

        }

    }

}
