<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\StockManagement;

use App\Casts\System\{ConfigurableDecimal};
use App\Helpers\System\{Numbers};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Warehouses\{InventoryStockAlert, Warehouse, WarehouseItem};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use DomainException;
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Carbon, Collection};

/**
 * Service class for managing Stock Management operations
 * Handles business logic for managing warehouse stock
 */
class StockManagementService {

    /**
     * Get paginated list of items with stock information
     *
     * @param  int  $warehouseId Warehouse ID
     * @param  int  $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getPaginatedList(
        int $warehouseId,
        int $perPage,
        string $search = "",
        string $situation = "",
        array $filters = []
    ) {

        return self::applyStockFilters(
            self::applySituationFilter(self::stockQuery($warehouseId, $search), $situation, $warehouseId),
            $filters,
            $warehouseId
        )
            ->paginate($perPage);

    }

    public static function getStockReport(
        int $warehouseId,
        string $search = "",
        string $situation = "",
        array $filters = []
    ): Collection {

        return self::applyStockFilters(
            self::applySituationFilter(self::stockQuery($warehouseId, $search), $situation, $warehouseId),
            $filters,
            $warehouseId
        )
            ->get();

    }

    public static function getConsolidatedStock(
        string $search = "",
        ?array $allowedWarehouseIds = null,
        string $situation = "",
        array $filters = []
    ): Collection {

        return self::applyStockFilters(
            self::applySituationFilter(
                self::consolidatedStockQuery($search, $allowedWarehouseIds),
                $situation,
                null,
                $allowedWarehouseIds
            ),
            $filters,
            null,
            $allowedWarehouseIds
        )
            ->get()
            ->map(fn(Item $item) => self::enrichConsolidatedStock($item));

    }

    public static function getPaginatedConsolidatedStock(
        string $search,
        ?array $allowedWarehouseIds,
        int $perPage,
        string $situation = "",
        array $filters = []
    ) {

        return self::applyStockFilters(
            self::applySituationFilter(
                self::consolidatedStockQuery($search, $allowedWarehouseIds),
                $situation,
                null,
                $allowedWarehouseIds
            ),
            $filters,
            null,
            $allowedWarehouseIds
        )
            ->paginate($perPage)
            ->through(fn(Item $item) => self::enrichConsolidatedStock($item));

    }

    private static function consolidatedStockQuery(
        string $search,
        ?array $allowedWarehouseIds
    ): Builder {

        return Item::query()
            ->where("type", "product")
            ->with("measurementUnit:id,abbreviation")
            ->with([
                "warehouseItems" => function($query) use ($allowedWarehouseIds) {

                    $query->with(["warehouse.branch:id,name"]);
                    $query->where("status", "active");

                    if($allowedWarehouseIds !== null) {

                        $query->whereIn("warehouse_id", $allowedWarehouseIds);

                    }

                },
            ])
            ->when(trim($search) !== "", function($query) use ($search) {

                $query->where(function($query) use ($search) {

                    $query->where("name", "like", "%{$search}%")
                        ->orWhere("internal_code", "like", "%{$search}%")
                        ->orWhere("barcode", "like", "%{$search}%");

                });

            })
            ->orderBy("name");

    }

    private static function enrichConsolidatedStock(Item $item): Item {

        $warehouses = $item->warehouseItems->map(function(WarehouseItem $warehouseItem) {

            $quantity = (float) ($warehouseItem->quantity ?? 0);
            $minimum = (float) ($warehouseItem->minimum_stock ?? 0);

            return [
                "warehouse_id" => (int) $warehouseItem->warehouse_id,
                "warehouse_name" => $warehouseItem->warehouse?->name,
                "branch_name" => $warehouseItem->warehouse?->branch?->name,
                "quantity" => $quantity,
                "minimum_stock" => $minimum,
                "requires_attention" => $quantity <= 0 || ($minimum > 0 && $quantity <= $minimum),
            ];

        })->values();

        $item->setAttribute("stock_quantity", Numbers::sumText($item->warehouseItems->map(fn(WarehouseItem $stock) => $stock->getRawOriginal("quantity"))));
        $item->setAttribute("minimum_stock", Numbers::sumText($item->warehouseItems->map(fn(WarehouseItem $stock) => $stock->getRawOriginal("minimum_stock"))));
        $item->setAttribute("warehouse_breakdown", $warehouses);
        $item->setAttribute("alert_warehouses_count", $warehouses->where("requires_attention", true)->count());

        return $item;

    }

    private static function stockQuery(
        int $warehouseId,
        string $search = ""
    ): Builder {

        $query = Item::query()
            ->where("type", "product")
            ->with("measurementUnit:id,abbreviation")
            ->withSum(["warehouseItems as stock_quantity" => function($query) use ($warehouseId) {

                $query->where("warehouse_id", $warehouseId)->where("status", "active");

            }], "quantity")
            ->withSum(["warehouseItems as minimum_stock" => function($query) use ($warehouseId) {

                $query->where("warehouse_id", $warehouseId)->where("status", "active");

            }], "minimum_stock")
            ->withCasts(["stock_quantity" => ConfigurableDecimal::class, "minimum_stock" => ConfigurableDecimal::class]);

        $search = trim($search);

        if($search !== "") {

            $query->where(function($query) use ($search) {

                $query->where("name", "like", "%{$search}%")
                    ->orWhere("internal_code", "like", "%{$search}%")
                    ->orWhere("barcode", "like", "%{$search}%");

            });

        }

        return $query->orderBy("name");

    }

    private static function applySituationFilter(
        Builder $query,
        string $situation,
        ?int $warehouseId,
        ?array $allowedWarehouseIds = null
    ): Builder {

        if(!in_array($situation, ["empty", "low", "healthy"], true)) {

            return $query;

        }

        if($warehouseId === null && $allowedWarehouseIds !== null) {

            $ids = array_values(array_filter(array_map("intval", $allowedWarehouseIds), fn(int $id) => $id > 0));

            if($ids === []) {

                return $situation === "empty" ? $query : $query->whereRaw("1 = 0");

            }

        }

        [$stock, $minimum] = self::stockExpressions($warehouseId, $allowedWarehouseIds);

        return match($situation) {
            "empty" => $query->whereRaw("{$stock} <= 0"),
            "low" => $query->whereRaw("{$stock} > 0 AND {$stock} <= {$minimum}"),
            "healthy" => $query->whereRaw("{$stock} > 0 AND {$stock} > {$minimum}"),
        };

    }

    private static function applyStockFilters(
        Builder $query,
        array $filters,
        ?int $warehouseId,
        ?array $allowedWarehouseIds = null
    ): Builder {

        $categoryId = (int) ($filters["category_id"] ?? 0);
        $brandId = (int) ($filters["brand_id"] ?? 0);
        $inactiveDays = (int) ($filters["inactive_days"] ?? 0);

        if($categoryId > 0) {
            $query->whereHas("categoryItems", fn(Builder $category) => $category->where("category_id", $categoryId));
        }
        if($brandId > 0) {
            $query->where("brand_id", $brandId);
        }

        [, $minimum] = self::stockExpressions($warehouseId, $allowedWarehouseIds);

        if(($filters["minimum_status"] ?? null) === "configured") {
            $query->whereRaw("{$minimum} > 0");
        }elseif(($filters["minimum_status"] ?? null) === "unconfigured") {
            $query->whereRaw("{$minimum} <= 0");
        }

        if(in_array($inactiveDays, [30, 60, 90], true)) {
            $query->whereDoesntHave("inventoryMovements", function(Builder $movement) use ($inactiveDays, $warehouseId, $allowedWarehouseIds) {
                $movement->where("created_at", ">=", now()->subDays($inactiveDays));
                if($warehouseId !== null) {
                    $movement->where("warehouse_id", $warehouseId);
                }elseif($allowedWarehouseIds !== null) {
                    $movement->whereIn("warehouse_id", $allowedWarehouseIds);
                }
            });
        }

        return $query;

    }

    private static function stockExpressions(?int $warehouseId, ?array $allowedWarehouseIds): array {

        $warehouseScope = $warehouseId !== null
            ? " AND wi.warehouse_id = ".(int) $warehouseId
            : "";
        if($warehouseId === null && $allowedWarehouseIds !== null) {
            $ids = array_values(array_filter(array_map("intval", $allowedWarehouseIds), fn(int $id) => $id > 0));
            $warehouseScope = $ids === []
                ? " AND 1 = 0"
                : " AND wi.warehouse_id IN (".implode(",", $ids).")";
        }

        $stock = Numbers::sqlRound("COALESCE(SUM(wi.quantity), 0)");
        $minimum = Numbers::sqlRound("COALESCE(SUM(wi.minimum_stock), 0)");

        return [
            "(SELECT {$stock} FROM warehouse_items wi WHERE wi.item_id = items.id AND wi.status = 'active'{$warehouseScope})",
            "(SELECT {$minimum} FROM warehouse_items wi WHERE wi.item_id = items.id AND wi.status = 'active'{$warehouseScope})",
        ];

    }

    /**
     * Validate warehouse belongs to company
     *
     * @param  int  $warehouseId Warehouse ID
     */
    public static function validateWarehouse(int $warehouseId): ?Warehouse {

        return Warehouse::where("id", $warehouseId)
            ->first();

    }

    /**
     * Update stock for items in a warehouse
     *
     * @param  int  $warehouseId Warehouse ID
     * @param  array  $items Array of items with stock quantities
     * @param  int|null  $userId User ID performing the action
     */
    public static function updateStock(int $warehouseId, array $items, ?int $userId = null): bool {

        DB::transaction(function() use ($warehouseId, $items, $userId) {

            $warehouse = Warehouse::query()
                ->findOrFail($warehouseId);

            foreach($items as $item) {

                $warehouseItem = WarehouseItem::where("warehouse_id", $warehouseId)
                    ->where("item_id", $item["id"])
                    ->first();

                $currentQuantity = Numbers::round(($warehouseItem?->quantity ?? 0));
                $resultingBalance = Numbers::round(($item["stock_quantity"] ?? 0));

                if($currentQuantity === $resultingBalance) {

                    continue;

                }

                InventoryMovementService::apply([
                    "warehouse_id" => $warehouseId,
                    "item_id" => (int) $item["id"],
                    "user_id" => $userId,
                    "movement_type" => InventoryMovementService::TYPE_CORRECTION,
                    "origin_type" => InventoryMovementService::ORIGIN_PHYSICAL_COUNT,
                    "resulting_balance" => $resultingBalance,
                    "reason" => "Corrección manual desde Inventario.",
                ]);

            }

        });

        return true;

    }

    public static function createManualMovement(
        int $warehouseId,
        int $itemId,
        string $movementType,
        ?float $quantity,
        ?float $resultingBalance,
        string $reason,
        string $originType = InventoryMovementService::ORIGIN_MANUAL,
        ?int $userId = null,
        ?float $unitCost = null
    ) {

        if(!in_array($originType, InventoryMovementService::manualOperationOrigins($movementType), true)) {

            throw new DomainException("El origen no corresponde al tipo de operación de inventario.");

        }

        return InventoryMovementService::apply([
            "warehouse_id" => $warehouseId,
            "item_id" => $itemId,
            "user_id" => $userId,
            "movement_type" => $movementType,
            "origin_type" => $originType,
            "quantity" => $quantity,
            "resulting_balance" => $resultingBalance,
            "unit_cost" => $unitCost,
            "reason" => $reason,
        ]);

    }

    public static function createManualMovements(
        int $warehouseId,
        string $movementType,
        string $originType,
        array $items,
        string $reason,
        ?int $userId = null
    ): array {

        return DB::transaction(function() use (
            $warehouseId,
            $movementType,
            $originType,
            $items,
            $reason,
            $userId
        ) {

            $movements = [];

            foreach($items as $item) {

                $movements[] = self::createManualMovement(
                    $warehouseId,
                    (int) $item["item_id"],
                    $movementType,
                    isset($item["quantity"]) ? (float) $item["quantity"] : null,
                    isset($item["resulting_balance"])
                        ? (float) $item["resulting_balance"]
                        : null,
                    $reason,
                    $originType,
                    $userId,
                    isset($item["unit_cost"]) && $item["unit_cost"] !== ""
                        ? (float) $item["unit_cost"]
                        : null
                );

            }

            return $movements;

        });

    }

    public static function getProductTraceability(int $warehouseId, int $itemId, int $perPage) {

        return InventoryMovementService::getKardexQuery(["warehouse_id" => $warehouseId, "item_id" => $itemId])
            ->setEagerLoads([])
            ->with("user:id,name")
            ->select(["id", "warehouse_id", "item_id", "user_id", "movement_type", "origin_type", "quantity_before", "quantity_change", "quantity_after", "reason", "created_at", "metadata"])
            ->orderByDesc("id")
            ->paginate($perPage)
            ->through(static fn($movement): array => [
                "id" => $movement->id,
                "created_at" => $movement->created_at?->toJSON(),
                "user" => $movement->user ? ["name" => $movement->user->name] : null,
                "movement_type" => $movement->movement_type,
                "origin_type" => $movement->origin_type,
                "quantity_before" => (float) $movement->quantity_before,
                "quantity_change" => (float) $movement->quantity_change,
                "quantity_after" => (float) $movement->quantity_after,
                "reason" => $movement->reason,
                "reference" => $movement->reference,
            ]);

    }

    public static function getKardex(array $filters, int $perPage) {

        return InventoryMovementService::getPaginatedKardex($filters, $perPage, self::kardexStockItems($filters));

    }

    public static function getKardexReport(array $filters): Collection {

        return InventoryMovementService::getKardexQuery($filters, self::kardexStockItems($filters))
            ->orderByDesc("id")
            ->get();

    }

    private static function kardexStockItems(array $filters): ?Builder {

        $stockKeys = ["situation", "category_id", "brand_id", "minimum_status", "inactive_days"];

        if(array_filter(array_intersect_key($filters, array_flip($stockKeys))) === []) {
            return null;
        }

        $warehouseId = isset($filters["warehouse_id"]) ? (int) $filters["warehouse_id"] : null;
        $allowedWarehouseIds = $filters["allowed_warehouse_ids"] ?? null;
        $items = Item::query()->select("items.id")->where("type", "product");

        self::applySituationFilter($items, (string) ($filters["situation"] ?? ""), $warehouseId, $allowedWarehouseIds);

        return self::applyStockFilters($items, $filters, $warehouseId, $allowedWarehouseIds);

    }

    public static function transfer(array $data) {

        return InventoryMovementService::transfer($data);

    }

    public static function getStockAlerts(
        array $filters,
        int $perPage
    ) {

        $warehouseId = (int) ($filters["warehouse_id"] ?? 0);
        $status = (string) ($filters["status"] ?? "open");
        $records = self::stockAlertsQuery(
            $warehouseId,
            $status,
            (string) ($filters["product_search"] ?? ""),
            (string) ($filters["minimum_status"] ?? "")
        )->paginate($perPage);

        if($status !== "open") {

            return $records;

        }

        return $records->through(static fn(object $row): array => [
            "id" => $row->id,
            "warehouse_item_id" => $row->warehouse_item_id,
            "quantity" => (float) $row->quantity,
            "minimum_stock" => (float) $row->minimum_stock,
            "status" => "open",
            "detected_at" => $row->detected_at ? Carbon::parse($row->detected_at)->toJSON() : null,
            "warehouse_item" => [
                "id" => $row->warehouse_item_id,
                "warehouse_id" => $warehouseId,
                "item" => [
                    "id" => $row->id,
                    "name" => $row->product_name,
                    "internal_code" => $row->internal_code,
                    "barcode" => $row->barcode,
                    "measurement_unit" => [
                        "id" => $row->measurement_unit_id,
                        "abbreviation" => $row->unit_abbreviation,
                    ],
                ],
            ],
        ]);

    }

    public static function stockAlertsQuery(int $warehouseId, string $status = "open", string $productSearch = "", string $minimumStatus = ""): Builder|QueryBuilder {

        if($status === "open") {

            return self::stockAlertsReportQuery($warehouseId, $productSearch, $minimumStatus);

        }

        $query = InventoryStockAlert::query()
            ->join("warehouse_items as alert_warehouse_items", "alert_warehouse_items.id", "=", "inventory_stock_alerts.warehouse_item_id")
            ->where("alert_warehouse_items.warehouse_id", $warehouseId)
            ->where("inventory_stock_alerts.status", $status)
            ->when(trim($productSearch) !== "", fn($query) => self::filterAlertProducts(
                $query->join("items as alert_products", "alert_products.id", "=", "alert_warehouse_items.item_id"),
                $productSearch,
                "alert_products"
            ))
            ->select([
                "inventory_stock_alerts.id",
                "inventory_stock_alerts.warehouse_item_id",
                "inventory_stock_alerts.quantity",
                "inventory_stock_alerts.minimum_stock",
                "inventory_stock_alerts.status",
                "inventory_stock_alerts.detected_at",
                "inventory_stock_alerts.resolved_at",
                "inventory_stock_alerts.resolved_by",
            ])
            ->with([
                "warehouseItem:id,warehouse_id,item_id",
                "warehouseItem.item:id,measurement_unit_id,internal_code,barcode,name",
                "warehouseItem.item.measurementUnit:id,abbreviation",
            ])
            ->when($status === "resolved", fn($query) => $query->with("resolvedBy:id,name"))
            ->orderByDesc("inventory_stock_alerts.detected_at")
            ->orderByDesc("inventory_stock_alerts.id");

        return self::filterAlertMinimum($query, $minimumStatus, Numbers::sqlRound("inventory_stock_alerts.minimum_stock"));

    }

    /** Filas planas para exportación: evita hidratar modelos, accessors y relaciones por alerta. */
    public static function stockAlertsReportQuery(int $warehouseId, string $productSearch = "", string $minimumStatus = ""): QueryBuilder {

        $quantity = Numbers::sqlRound("SUM(quantity)");
        $minimum = Numbers::sqlRound("SUM(minimum_stock)");
        $stock = DB::table("warehouse_items")
            ->where("warehouse_id", $warehouseId)
            ->where("status", "active")
            ->select("item_id")
            ->selectRaw("MIN(id) as warehouse_item_id, {$quantity} as quantity, {$minimum} as minimum_stock")
            ->groupBy("item_id");
        $detected = DB::table("inventory_stock_alerts as alerts")
            ->join("warehouse_items as alert_stock", "alert_stock.id", "=", "alerts.warehouse_item_id")
            ->where("alert_stock.warehouse_id", $warehouseId)
            ->where("alert_stock.status", "active")
            ->where("alerts.status", "open")
            ->select("alert_stock.item_id")
            ->selectRaw("MAX(alerts.detected_at) as detected_at")
            ->groupBy("alert_stock.item_id");

        $query = DB::table("items as products")
            ->leftJoinSub($stock, "stock", "stock.item_id", "=", "products.id")
            ->leftJoinSub($detected, "detected", "detected.item_id", "=", "products.id")
            ->leftJoin("measurement_units as units", "units.id", "=", "products.measurement_unit_id")
            ->where("products.type", "product")
            ->where(static fn($query) => $query
                ->whereRaw("COALESCE(stock.quantity, 0) <= 0")
                ->orWhere(static fn($low) => $low
                    ->where("stock.minimum_stock", ">", 0)
                    ->whereColumn("stock.quantity", "<=", "stock.minimum_stock")
                )
            )
            ->select([
                "products.id",
                "stock.warehouse_item_id",
                DB::raw("COALESCE(stock.quantity, 0) as quantity"),
                DB::raw("COALESCE(stock.minimum_stock, 0) as minimum_stock"),
                "detected.detected_at",
                "products.name as product_name",
                "products.internal_code",
                "products.barcode",
                "products.measurement_unit_id",
                "units.abbreviation as unit_abbreviation",
            ]);

        $query = self::filterAlertProducts($query, $productSearch, "products");

        return self::filterAlertMinimum($query, $minimumStatus, "COALESCE(stock.minimum_stock, 0)")
            ->orderByDesc("detected.detected_at")
            ->orderBy("products.name")
            ->orderBy("products.id");

    }

    private static function filterAlertMinimum(Builder|QueryBuilder $query, string $status, string $column): Builder|QueryBuilder {

        if(in_array($status, ["configured", "unconfigured"], true)) {

            $operator = $status === "configured" ? ">" : "<=";
            $query->where(DB::raw($column), $operator, 0);

        }

        return $query;

    }

    private static function filterAlertProducts(Builder|QueryBuilder $query, string $search, string $alias): Builder|QueryBuilder {

        $term = trim($search);

        if($term === "") {

            return $query;

        }

        $pattern = "%{$term}%";

        return $query->where(static fn($filter) => $filter
            ->where("{$alias}.name", "like", $pattern)
            ->orWhere("{$alias}.internal_code", "like", $pattern)
            ->orWhere("{$alias}.barcode", "like", $pattern)
        );

    }

}
