<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\Inventory;

use App\Helpers\System\{Utilities};
use App\Mail\{InventoryStockAlertMail};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{Company};
use App\Models\System\Warehouses\{InventoryMovement, InventoryStockAlert, Warehouse, WarehouseItem};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use DomainException;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB, Log, Mail};
use Illuminate\Support\{Str};
use Throwable;

final class InventoryMovementService {
    public const TYPE_ENTRY = "entry";

    public const TYPE_EXIT = "exit";

    public const TYPE_CORRECTION = "correction";

    public const ORIGIN_PRODUCT_OPENING = "product_opening";

    public const ORIGIN_MANUAL = "manual";

    public const ORIGIN_SALE = "sale";

    public const ORIGIN_SALE_DELIVERY = "sale_delivery";

    public const ORIGIN_SALE_CANCELLATION = "sale_cancellation";

    public const ORIGIN_PURCHASE = "purchase";

    public const ORIGIN_PURCHASE_CANCELLATION = "purchase_cancellation";

    public const ORIGIN_TRANSFER_OUT = "transfer_out";

    public const ORIGIN_TRANSFER_IN = "transfer_in";

    public const ORIGIN_REPLENISHMENT = "replenishment";

    public const ORIGIN_CUSTOMER_RETURN = "customer_return";

    public const ORIGIN_SUPPLIER_RETURN = "supplier_return";

    public const ORIGIN_PHYSICAL_COUNT = "physical_count";

    public const ORIGIN_RECIPE_SALE = "recipe_sale";

    public const ORIGIN_RECIPE_WASTE = "recipe_waste";

    private const MOVEMENT_TYPES = [
        self::TYPE_ENTRY,
        self::TYPE_EXIT,
        self::TYPE_CORRECTION,
    ];

    public static function apply(array $data): InventoryMovement {

        return DB::transaction(function() use ($data) {

            $companyId = app(TenantCompanyContext::class)->id();
            $warehouseId = (int) ($data["warehouse_id"] ?? 0);
            $itemId = (int) ($data["item_id"] ?? 0);
            $type = (string) ($data["movement_type"] ?? "");
            $originType = trim((string) ($data["origin_type"] ?? ""));
            $reason = trim((string) ($data["reason"] ?? ""));

            if(!in_array($type, self::MOVEMENT_TYPES, true)) {

                throw new DomainException("El tipo de movimiento de inventario no es válido.");

            }

            if($originType === "" || $reason === "") {

                throw new DomainException("El origen y el motivo del movimiento son obligatorios.");

            }

            self::assertWarehouseAndItemExistInTenant($warehouseId, $itemId);

            $warehouseItem = WarehouseItem::where("warehouse_id", $warehouseId)
                ->where("item_id", $itemId)
                ->lockForUpdate()
                ->first();

            if(!$warehouseItem) {

                WarehouseItem::create([
                    "warehouse_id" => $warehouseId,
                    "item_id" => $itemId,
                    "quantity" => 0,
                    "minimum_stock" => 0,
                    "average_cost" => 0,
                    "inventory_value" => 0,
                    "status" => "active",
                    "created_at" => now(),
                    "created_by" => $data["user_id"] ?? null,
                ]);

                $warehouseItem = WarehouseItem::where("warehouse_id", $warehouseId)
                    ->where("item_id", $itemId)
                    ->lockForUpdate()
                    ->firstOrFail();

            }

            $quantityBefore = Utilities::round((float) $warehouseItem->quantity, null, $companyId);

            $quantityChange = self::resolveQuantityChange($type, $quantityBefore, $data);
            $quantityAfter = Utilities::round($quantityBefore + $quantityChange, null, $companyId);
            $valueBefore = Utilities::round((float) ($warehouseItem->inventory_value ?? 0), null, $companyId);
            $currentAverageCost = Utilities::round((float) ($warehouseItem->average_cost ?? 0), null, $companyId);

            if(abs($quantityChange) < 0.00001) {

                throw new DomainException("El movimiento no modifica el saldo actual.");

            }

            if($quantityAfter < 0 && !($data["allow_negative"] ?? false)) {

                throw new DomainException("La salida supera el stock disponible en el almacén.");

            }

            $unitCost = self::resolveUnitCost($type, $quantityChange, $currentAverageCost, $data);

            $valueChange = Utilities::round($quantityChange * $unitCost, null, $companyId);
            $valueAfter = Utilities::round($valueBefore + $valueChange, null, $companyId);
            $averageCost = self::resolveAverageCost(
                $type,
                $quantityBefore,
                $quantityAfter,
                $valueAfter,
                $currentAverageCost,
                $unitCost
            );

            $warehouseItem->update([
                "quantity" => $quantityAfter,
                "average_cost" => $averageCost,
                "inventory_value" => $valueAfter,
                "status" => "active",
                "updated_at" => now(),
                "updated_by" => $data["user_id"] ?? null,
            ]);

            $metadata = $data["metadata"] ?? [];

            if(!empty($data["reference"])) {

                $metadata["reference"] = $data["reference"];

            }

            $movement = InventoryMovement::create([
                "warehouse_id" => $warehouseId,
                "item_id" => $itemId,
                "user_id" => $data["user_id"] ?? null,
                "movement_type" => $type,
                "origin_type" => $originType,
                "origin_id" => $data["origin_id"] ?? null,
                "quantity_before" => $quantityBefore,
                "quantity_change" => $quantityChange,
                "quantity_after" => $quantityAfter,
                "unit_cost" => $unitCost,
                "value_before" => $valueBefore,
                "value_change" => $valueChange,
                "value_after" => $valueAfter,
                "reason" => $reason,
                "metadata" => $metadata ?: null,
                "created_at" => now(),
            ]);

            self::syncMinimumStockAlert(
                $warehouseItem->fresh(),
                $companyId,
                $data["user_id"] ?? null
            );

            return $movement;

        });

    }

    public static function transfer(array $data): array {

        return DB::transaction(function() use ($data) {

            $companyId = app(TenantCompanyContext::class)->id();
            $sourceWarehouseId = (int) ($data["source_warehouse_id"] ?? 0);
            $destinationWarehouseId = (int) ($data["destination_warehouse_id"] ?? 0);
            $items = is_array($data["items"] ?? null) ? $data["items"] : [];
            $reason = trim((string) ($data["reason"] ?? ""));

            if($sourceWarehouseId === $destinationWarehouseId) {

                throw new DomainException("Selecciona almacenes diferentes para el traslado.");

            }

            if(empty($items)) {

                throw new DomainException("Agrega al menos un producto al traslado.");

            }

            if(count($items) > 100) {

                throw new DomainException("Puedes trasladar hasta 100 productos por operación.");

            }

            if($reason === "") {

                throw new DomainException("El motivo del traslado es obligatorio.");

            }

            $reference = "TRF-".strtoupper(Str::random(12));

            $movements = [];
            $processedItemIds = [];

            foreach($items as $item) {

                $itemId = (int) ($item["item_id"] ?? 0);
                $quantity = Utilities::round((float) ($item["quantity"] ?? 0), null, $companyId);

                if($quantity <= 0) {

                    throw new DomainException("Todas las cantidades deben ser mayores que cero.");

                }

                if(in_array($itemId, $processedItemIds, true)) {

                    throw new DomainException("No repitas un producto en el mismo traslado.");

                }

                $processedItemIds[] = $itemId;

                self::assertWarehouseAndItemExistInTenant($sourceWarehouseId, $itemId);
                self::assertWarehouseAndItemExistInTenant($destinationWarehouseId, $itemId);

                $metadata = [
                    "reference" => $reference,
                    "source_warehouse_id" => $sourceWarehouseId,
                    "destination_warehouse_id" => $destinationWarehouseId,
                ];

                $exit = self::apply([
                    "warehouse_id" => $sourceWarehouseId,
                    "item_id" => $itemId,
                    "user_id" => $data["user_id"] ?? null,
                    "movement_type" => self::TYPE_EXIT,
                    "origin_type" => self::ORIGIN_TRANSFER_OUT,
                    "quantity" => $quantity,
                    "reason" => $reason,
                    "reference" => $reference,
                    "metadata" => $metadata,
                ]);

                $entry = self::apply([
                    "warehouse_id" => $destinationWarehouseId,
                    "item_id" => $itemId,
                    "user_id" => $data["user_id"] ?? null,
                    "movement_type" => self::TYPE_ENTRY,
                    "origin_type" => self::ORIGIN_TRANSFER_IN,
                    "quantity" => $quantity,
                    "unit_cost" => (float) $exit->unit_cost,
                    "reason" => $reason,
                    "reference" => $reference,
                    "metadata" => $metadata,
                ]);

                $movements[] = [
                    "item_id" => $itemId,
                    "exit" => $exit,
                    "entry" => $entry,
                ];

            }

            return [
                "reference" => $reference,
                "items_count" => count($movements),
                "movements" => $movements,
            ];

        });

    }

    public static function getPaginatedKardex(
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {

        return self::getKardexQuery($filters)
            ->orderByDesc("id")
            ->paginate($perPage);

    }

    public static function getKardexQuery(
        array $filters = []
    ): Builder {

        $query = InventoryMovement::query()
            ->with([
                "warehouse.branch:id,name,status",
                "item:id,internal_code,barcode,name",
                "user:id,name",
            ]);

        if(!empty($filters["warehouse_id"])) {

            $query->where("warehouse_id", (int) $filters["warehouse_id"]);

        }

        if(!empty($filters["item_id"])) {

            $query->where("item_id", (int) $filters["item_id"]);

        }

        if(!empty($filters["movement_type"])) {

            $query->where("movement_type", $filters["movement_type"]);

        }

        if(!empty($filters["origin_types"]) && is_array($filters["origin_types"])) {

            $query->whereIn("origin_type", $filters["origin_types"]);

        }

        $productSearch = trim((string) ($filters["product_search"] ?? ""));

        if($productSearch !== "") {

            $query->whereHas("item", function($query) use ($productSearch) {

                $query->where(function($query) use ($productSearch) {

                    $query->where("name", "like", "%{$productSearch}%")
                        ->orWhere("internal_code", "like", "%{$productSearch}%")
                        ->orWhere("barcode", "like", "%{$productSearch}%");

                });

            });

        }

        if(!empty($filters["date_from"])) {

            $query->where("created_at", ">=", Utilities::startOfDay($filters["date_from"]));

        }

        if(!empty($filters["date_to"])) {

            $query->where("created_at", "<=", Utilities::endOfDay($filters["date_to"]));

        }

        return $query;

    }

    private static function resolveQuantityChange(
        string $type,
        float $quantityBefore,
        array $data
    ): float {

        if($type === self::TYPE_CORRECTION) {

            if(!array_key_exists("resulting_balance", $data)) {

                throw new DomainException("Debes indicar el saldo físico resultante de la corrección.");

            }

            $resultingBalance = Utilities::round((float) $data["resulting_balance"]);

            if($resultingBalance < 0) {

                throw new DomainException("El saldo corregido no puede ser negativo.");

            }

            return Utilities::round($resultingBalance - $quantityBefore);

        }

        $quantity = Utilities::round((float) ($data["quantity"] ?? 0));

        if($quantity <= 0) {

            throw new DomainException("La cantidad debe ser mayor que cero.");

        }

        return $type === self::TYPE_ENTRY ? $quantity : -$quantity;

    }

    private static function resolveUnitCost(
        string $type,
        float $quantityChange,
        float $currentAverageCost,
        array $data
    ): float {

        if($type === self::TYPE_EXIT || $quantityChange < 0) {

            return $currentAverageCost;

        }

        if(array_key_exists("unit_cost", $data) && $data["unit_cost"] !== null) {

            $unitCost = Utilities::round((float) $data["unit_cost"]);

            if($unitCost < 0) {

                throw new DomainException("El costo unitario no puede ser negativo.");

            }

            return $unitCost;

        }

        return $currentAverageCost;

    }

    private static function resolveAverageCost(
        string $type,
        float $quantityBefore,
        float $quantityAfter,
        float $valueAfter,
        float $currentAverageCost,
        float $unitCost
    ): float {

        if(abs($quantityAfter) < 0.00001) {

            return 0;

        }

        if($type === self::TYPE_ENTRY && $quantityAfter > 0) {

            return Utilities::round($valueAfter / $quantityAfter);

        }

        if($quantityBefore <= 0 && $quantityAfter > 0) {

            return $unitCost;

        }

        return $currentAverageCost;

    }

    private static function assertWarehouseAndItemExistInTenant(
        int $warehouseId,
        int $itemId
    ): void {

        $warehouseExists = Warehouse::whereKey($warehouseId)
            ->exists();

        $itemExists = Item::whereKey($itemId)
            ->where("type", "product")
            ->exists();

        if(!$warehouseExists || !$itemExists) {

            throw new DomainException("El producto o el almacén no pertenece a la empresa.");

        }

    }

    private static function syncMinimumStockAlert(
        WarehouseItem $warehouseItem,
        int $companyId,
        ?int $userId
    ): void {

        $quantity = (float) $warehouseItem->quantity;
        $minimum = (float) $warehouseItem->minimum_stock;
        $isLow = $minimum > 0 && $quantity <= $minimum;
        $openAlert = InventoryStockAlert::query()
            ->where("warehouse_item_id", $warehouseItem->id)
            ->where("status", "open")
            ->latest("id")
            ->first();

        if($isLow) {

            if($openAlert) {

                $openAlert->update([
                    "quantity" => $quantity,
                    "minimum_stock" => $minimum,
                ]);

                return;

            }

            $alert = InventoryStockAlert::create([
                "warehouse_item_id" => $warehouseItem->id,
                "quantity" => $quantity,
                "minimum_stock" => $minimum,
                "status" => "open",
                "detected_at" => now(),
            ]);

            self::notifyMinimumStockAlert($alert, $companyId);

            return;

        }

        if($openAlert) {

            $openAlert->update([
                "quantity" => $quantity,
                "minimum_stock" => $minimum,
                "status" => "resolved",
                "resolved_at" => now(),
                "resolved_by" => $userId,
            ]);

        }

    }

    private static function notifyMinimumStockAlert(
        InventoryStockAlert $alert,
        int $companyId
    ): void {

        $enabled = (bool) CompanySettingService::value(
            CompanySettingService::INVENTORY_POLICIES,
            "stock_alert_email_enabled",
            false
        );

        if(!$enabled) {

            return;

        }

        $recipient = (string) CompanySettingService::value(
            CompanySettingService::INVENTORY_POLICIES,
            "stock_alert_email_to",
            ""
        );

        if($recipient === "") {

            $recipient = (string) Company::whereKey($companyId)->value("email");

        }

        if(!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {

            return;

        }

        try {

            $alert->loadMissing([
                "warehouseItem.item",
                "warehouseItem.warehouse.branch",
            ]);

            Mail::to($recipient)->send(new InventoryStockAlertMail($alert));

        }catch(Throwable $exception) {

            Log::warning("No se pudo enviar la alerta de stock mínimo.", [
                "inventory_stock_alert_id" => $alert->id,
                "error" => $exception->getMessage(),
            ]);

        }

    }
}
