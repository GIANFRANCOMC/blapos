<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Models\System\Warehouses\{WarehouseItem};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use stdClass;

final class CashRegisterConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "cash_registers:v1";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::for($companyId, $userId);

        return self::data([
            "branches" => $references->activeBranches(),
            "registers" => $references->cashRegisters(),
            "inventoryItems" => self::inventoryItems($references),
            "paymentMethods" => $references->paymentMethodsFor("sale"),
            "statuses" => [
                ["id" => "open", "label" => "Abierta"],
                ["id" => "closed", "label" => "Cerrada"],
                ["id" => "cancelled", "label" => "Anulada"],
            ],
            "movementTypes" => [
                ["id" => "opening", "label" => "Apertura"],
                ["id" => "sale", "label" => "Venta"],
                ["id" => "income", "label" => "Ingreso"],
                ["id" => "expense", "label" => "Salida"],
                ["id" => "adjustment", "label" => "Ajuste"],
                ["id" => "closing", "label" => "Cierre"],
            ],
        ]);

    }

    private static function inventoryItems(CompanyReferenceDataService $references) {

        $branchIds = $references->activeBranches()->pluck("id")->all();

        return WarehouseItem::query()
            ->with(["warehouse.branch", "item.brand"])
            ->where("status", "active")
            ->whereHas("warehouse", function($query) use ($branchIds) {

                $query->where("status", "active")
                    ->whereIn("branch_id", $branchIds);

            })
            ->whereHas("item", function($query) {

                $query->where("type", "product")
                    ->where("status", "active");

            })
            ->orderBy("warehouse_id")
            ->get()
            ->map(function(WarehouseItem $warehouseItem) {

                return [
                    "warehouse_id" => $warehouseItem->warehouse_id,
                    "warehouse_name" => $warehouseItem->warehouse?->name,
                    "branch_id" => $warehouseItem->warehouse?->branch_id,
                    "branch_name" => $warehouseItem->warehouse?->branch?->name,
                    "item_id" => $warehouseItem->item_id,
                    "item_name" => $warehouseItem->item?->name,
                    "item_internal_code" => $warehouseItem->item?->internal_code,
                    "brand_name" => $warehouseItem->item?->brand?->name,
                    "system_quantity" => (float) $warehouseItem->quantity,
                ];

            })
            ->values();

    }
}
