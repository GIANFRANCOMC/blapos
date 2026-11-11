<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\Inventory;

use App\Helpers\System\{Numbers, StockStatus, Utilities};
use App\Mail\{InventoryStockAlertMail};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use App\Models\System\Warehouses\{InventoryMovement, InventoryStockAlert, Warehouse, WarehouseItem};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use DomainException;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB, Log, Mail};
use Illuminate\Support\{Collection, Str};
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

    public static function manualOperationOrigins(string $movementType): array {

        return match($movementType) {
            self::TYPE_ENTRY => [self::ORIGIN_MANUAL, self::ORIGIN_CUSTOMER_RETURN],
            self::TYPE_EXIT => [self::ORIGIN_MANUAL, self::ORIGIN_SUPPLIER_RETURN],
            self::TYPE_CORRECTION => [self::ORIGIN_PHYSICAL_COUNT],
            default => [],
        };

    }

    public static function apply(array $data): InventoryMovement {

        return DB::transaction(function() use ($data) {

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

            $quantityBefore = Numbers::round($warehouseItem->quantity);

            $quantityChange = self::resolveQuantityChange($type, $quantityBefore, $data);
            $quantityAfter = Numbers::sum([$quantityBefore, $quantityChange]);
            $valueBefore = Numbers::round(($warehouseItem->inventory_value ?? 0));
            $currentAverageCost = Numbers::round(($warehouseItem->average_cost ?? 0));

            if($quantityChange === 0.0) {

                throw new DomainException("El movimiento no modifica el saldo actual.");

            }

            if($quantityAfter < 0 && !($data["allow_negative"] ?? false)) {

                throw new DomainException("La salida supera el stock disponible en el almacén.");

            }

            $unitCost = self::resolveUnitCost($type, $quantityChange, $currentAverageCost, $data);

            $valueChange = match (true) {
                $type === self::TYPE_ENTRY && isset($data["entry_value"]) => Numbers::round($data["entry_value"]),
                $quantityAfter === 0.0 => -$valueBefore,
                $quantityChange < 0 && $quantityAfter > 0 => Numbers::proportion($valueBefore, $quantityChange, $quantityBefore),
                default => Numbers::multiply($quantityChange, $unitCost),
            };
            $valueAfter = Numbers::sum([$valueBefore, $valueChange]);
            $averageCost = match (true) {
                $quantityAfter === 0.0 => 0.0,
                $quantityAfter > 0 => Numbers::divide($valueAfter, $quantityAfter),
                default => $currentAverageCost,
            };

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
                $data["user_id"] ?? null
            );

            return $movement;

        });

    }

    /** Ajuste individual atómico; conserva kardex, valorización y alertas. */
    public static function updateStockLevels(array $data): WarehouseItem {

        return DB::transaction(function() use ($data): WarehouseItem {

            $warehouseId = (int) $data["warehouse_id"];
            $itemId = (int) $data["item_id"];
            self::assertWarehouseAndItemExistInTenant($warehouseId, $itemId);

            WarehouseItem::query()->insertOrIgnore([
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
            $stock = WarehouseItem::query()->where("warehouse_id", $warehouseId)
                ->where("item_id", $itemId)->lockForUpdate()->firstOrFail();
            $before = Numbers::round($stock->quantity);
            $minimumBefore = Numbers::round($stock->minimum_stock);
            $quantity = Numbers::round($data["quantity"]);
            $minimum = Numbers::round($data["minimum_stock"]);

            if($before !== Numbers::round($data["expected_quantity"])
                || $minimumBefore !== Numbers::round($data["expected_minimum_stock"])) {

                throw new DomainException("El inventario cambió mientras editabas. Revisa los valores actualizados y guarda nuevamente.", 409);

            }

            if(($quantity < 0 && $quantity !== $before) || $minimum < 0 || !is_finite($quantity) || !is_finite($minimum)) {

                throw new DomainException("El stock actual y el mínimo deben ser números mayores o iguales a cero.");

            }

            if($minimum !== $minimumBefore || $stock->status !== "active") {

                $stock->update(["minimum_stock" => $minimum, "status" => "active", "updated_by" => $data["user_id"] ?? null, "updated_at" => now()]);

            }

            if($quantity !== $before) {

                self::apply([
                    "warehouse_id" => $warehouseId,
                    "item_id" => $itemId,
                    "user_id" => $data["user_id"] ?? null,
                    "movement_type" => self::TYPE_CORRECTION,
                    "origin_type" => self::ORIGIN_PHYSICAL_COUNT,
                    "resulting_balance" => $quantity,
                    "reason" => trim((string) ($data["reason"] ?? "")) ?: "Ajuste individual desde Control de stock.",
                    "metadata" => ["source" => "stock_editor", "minimum_stock_before" => $minimumBefore, "minimum_stock_after" => $minimum],
                ]);

            } else {

                self::syncMinimumStockAlert($stock->fresh(), $data["user_id"] ?? null);

            }

            return $stock->fresh();

        });

    }

    public static function transfer(array $data): array {

        return DB::transaction(function() use ($data) {

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
                $quantity = Numbers::round(($item["quantity"] ?? 0));

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
                    "entry_value" => -(float) $exit->value_change,
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
        ?int $perPage = null,
        ?Builder $stockItems = null
    ): LengthAwarePaginator {

        $page = self::getKardexQuery($filters, $stockItems)
            ->orderByDesc("id")
            ->paginate($perPage ?? Utilities::$per_page_default);

        $originTypes = $filters["origin_types"] ?? [];
        $transferOrigins = [self::ORIGIN_TRANSFER_OUT, self::ORIGIN_TRANSFER_IN];

        if(!is_array($originTypes) || !array_intersect($originTypes, $transferOrigins)) {

            return $page;

        }

        $warehouseIds = $page->getCollection()
            ->flatMap(fn(InventoryMovement $movement) => [
                (int) ($movement->metadata["source_warehouse_id"] ?? 0),
                (int) ($movement->metadata["destination_warehouse_id"] ?? 0),
            ])
            ->filter(fn(int $warehouseId) => $warehouseId > 0)
            ->unique()
            ->values()
            ->all();

        if(!$warehouseIds) {

            return $page;

        }

        $warehouseQuery = Warehouse::query()
            ->with("branch:id,name")
            ->whereIn("id", $warehouseIds);

        if(($filters["allowed_warehouse_ids"] ?? null) !== null) {

            $warehouseQuery->whereIn("id", $filters["allowed_warehouse_ids"]);

        }

        $warehouses = $warehouseQuery
            ->get(["id", "branch_id", "name"])
            ->keyBy("id");

        $page->getCollection()->each(function(InventoryMovement $movement) use ($warehouses) {

            $sourceId = (int) ($movement->metadata["source_warehouse_id"] ?? 0);
            $destinationId = (int) ($movement->metadata["destination_warehouse_id"] ?? 0);

            foreach([
                "transfer_source_warehouse" => $sourceId,
                "transfer_destination_warehouse" => $destinationId,
            ] as $attribute => $warehouseId) {

                $warehouse = $warehouses->get($warehouseId);

                $movement->setAttribute($attribute, $warehouse ? [
                    "name" => $warehouse->name,
                    "branch_name" => $warehouse->branch?->name,
                ] : null);

            }

        });

        return $page;

    }

    public static function getKardexUsers(array $warehouseIds): Collection {

        $authors = InventoryMovement::query()->select("user_id")->whereIn("warehouse_id", $warehouseIds);

        return User::query()->whereIn("id", $authors)->orderBy("name")->toBase()->get(["id", "name"]);

    }

    public static function getKardexQuery(
        array $filters = [],
        ?Builder $stockItems = null
    ): Builder {

        $query = InventoryMovement::query()
            ->with([
                "warehouse.branch:id,name,status",
                "item:id,measurement_unit_id,internal_code,barcode,name",
                "item.measurementUnit:id,abbreviation",
                "user:id,name",
            ]);

        if($stockItems !== null) {
            $query->whereIn("inventory_movements.item_id", $stockItems);
        }

        if(array_key_exists("allowed_warehouse_ids", $filters) && $filters["allowed_warehouse_ids"] !== null) {

            $query->whereIn("warehouse_id", $filters["allowed_warehouse_ids"]);

        }

        if(!empty($filters["warehouse_id"])) {

            $query->where("warehouse_id", (int) $filters["warehouse_id"]);

        }

        if(!empty($filters["item_id"])) {

            $query->where("item_id", (int) $filters["item_id"]);

        }

        if(!empty($filters["movement_type"])) {

            $query->where("movement_type", $filters["movement_type"]);

        }

        if(!empty($filters["user_id"])) {
            $query->where("user_id", (int) $filters["user_id"]);
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

            $resultingBalance = Numbers::round($data["resulting_balance"]);

            if($resultingBalance < 0) {

                throw new DomainException("El saldo corregido no puede ser negativo.");

            }

            return Numbers::subtract($resultingBalance, $quantityBefore);

        }

        $quantity = Numbers::round(($data["quantity"] ?? 0));

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

            $unitCost = Numbers::round($data["unit_cost"]);

            if($unitCost < 0) {

                throw new DomainException("El costo unitario no puede ser negativo.");

            }

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
        ?int $userId
    ): void {

        $quantity = (float) $warehouseItem->quantity;
        $minimum = (float) $warehouseItem->minimum_stock;
        $requiresAttention = StockStatus::fromLevels($warehouseItem->quantity, $warehouseItem->minimum_stock) !== StockStatus::Healthy;
        $openAlert = InventoryStockAlert::query()
            ->where("warehouse_item_id", $warehouseItem->id)
            ->where("status", "open")
            ->latest("id")
            ->first();

        if($requiresAttention) {

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

            self::notifyMinimumStockAlert($alert);

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

    private static function notifyMinimumStockAlert(InventoryStockAlert $alert): void {

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

            $recipient = (string) app(TenantCompanyContext::class)->get()->email;

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
