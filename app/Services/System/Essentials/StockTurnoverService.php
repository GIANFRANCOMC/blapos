<?php

declare(strict_types=1);

namespace App\Services\System\Essentials;

use App\Helpers\System\{Numbers, StockStatus, Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use Carbon\{CarbonImmutable};
use Illuminate\Database\Query\{Builder};
use Illuminate\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};

final class StockTurnoverService {

    private const PER_PAGE = 10;

    public function filters(User $user): array {

        $today = CarbonImmutable::today(config("app.timezone"));
        $warehouses = $this->warehouses($user)
            ->orderBy("branches.name")->orderBy("warehouses.name")->orderBy("warehouses.id")
            ->get(["warehouses.id", "warehouses.name", "branches.id as branch_id", "branches.name as branch_name"]);

        return [
            "defaults" => ["from" => $today->subDays(29)->toDateString(), "to" => $today->toDateString()],
            "branches" => $warehouses->unique("branch_id")->values()->map(fn(object $warehouse): array => [
                "id" => (int) $warehouse->branch_id,
                "name" => $warehouse->branch_name,
            ]),
            "warehouses" => $warehouses,
        ];

    }

    public function report(User $user, array $filters): LengthAwarePaginator {

        $warehouses = $this->warehouses($user, $filters);
        $outgoing = $this->netOutgoing((clone $warehouses)->select("warehouses.id"), $filters);
        $quantity = Numbers::sqlRound("stock.quantity");
        $minimum = Numbers::sqlRound("stock.minimum_stock");

        return DB::transaction(fn(): LengthAwarePaginator => $warehouses
            ->join("warehouse_items as stock", "stock.warehouse_id", "=", "warehouses.id")
            ->join("items", "items.id", "=", "stock.item_id")
            ->leftJoin("measurement_units as units", "units.id", "=", "items.measurement_unit_id")
            ->leftJoinSub($outgoing, "outgoing", function($join): void {
                $join->on("outgoing.warehouse_id", "=", "stock.warehouse_id")
                    ->on("outgoing.item_id", "=", "stock.item_id");
            })
            ->where("stock.status", "active")
            ->where("items.type", Item::TYPE_PRODUCT)
            ->where("items.status", "active")
            ->when($filters["replenishment_only"] ?? false, fn(Builder $query) => $query
                ->whereRaw("({$quantity} <= 0 OR {$quantity} <= {$minimum})"))
            ->orderByRaw("CASE WHEN {$quantity} <= 0 THEN 0 WHEN {$quantity} <= {$minimum} THEN 1 ELSE 2 END")
            ->orderBy("items.name")->orderBy("items.id")->orderBy("warehouses.id")
            ->paginate(self::PER_PAGE, [
                "stock.id", "stock.quantity", "stock.minimum_stock", "outgoing.net_outgoing",
                "items.id as item_id", "items.name", "items.internal_code", "items.barcode", "units.abbreviation",
                "branches.name as branch_name", "warehouses.name as warehouse_name",
            ], "page", (int) ($filters["page"] ?? 1))
            ->through(fn(object $row): array => $this->row($row)));

    }

    private function warehouses(User $user, array $filters = []): Builder {

        $allowedIds = AccessScopeService::allowedIds($user, AccessScopeService::WAREHOUSE);

        return DB::table("warehouses")
            ->join("branches", "branches.id", "=", "warehouses.branch_id")
            ->where("warehouses.status", "active")
            ->where("branches.status", "active")
            ->when($allowedIds !== null, fn(Builder $query) => $query->whereIn("warehouses.id", $allowedIds))
            ->when($filters["branch_id"] ?? null, fn(Builder $query, $id) => $query->where("warehouses.branch_id", $id))
            ->when($filters["warehouse_id"] ?? null, fn(Builder $query, $id) => $query->where("warehouses.id", $id));

    }

    private function netOutgoing(Builder $warehouseIds, array $filters): Builder {

        return DB::table("inventory_movements")
            ->select("warehouse_id", "item_id")
            ->selectRaw("-SUM(quantity_change) AS net_outgoing")
            ->whereIn("warehouse_id", $warehouseIds)
            ->whereBetween("created_at", [Utilities::startOfDay($filters["from"]), Utilities::endOfDay($filters["to"])])
            ->where(function(Builder $movements): void {
                $movements->where(fn(Builder $exits) => $exits->where("quantity_change", "<", 0)->whereIn("origin_type", [
                    InventoryMovementService::ORIGIN_SALE,
                    InventoryMovementService::ORIGIN_SALE_DELIVERY,
                    InventoryMovementService::ORIGIN_RECIPE_SALE,
                ]))->orWhere(fn(Builder $entries) => $entries->where("quantity_change", ">", 0)->whereIn("origin_type", [
                    InventoryMovementService::ORIGIN_CUSTOMER_RETURN,
                    InventoryMovementService::ORIGIN_SALE_CANCELLATION,
                ]));
            })
            ->groupBy("warehouse_id", "item_id");

    }

    private function row(object $row): array {

        $quantity = Numbers::storage($row->quantity);
        $minimum = Numbers::storage($row->minimum_stock);

        return [
            "id" => (int) $row->id,
            "product" => [
                "id" => (int) $row->item_id,
                "name" => $row->name,
                "internal_code" => $row->internal_code,
                "barcode" => $row->barcode,
            ],
            "measurement_unit" => ["abbreviation" => $row->abbreviation],
            "branch_name" => $row->branch_name,
            "warehouse_name" => $row->warehouse_name,
            "quantity" => $quantity,
            "minimum_stock" => Numbers::compare($minimum, 0) > 0 ? $minimum : null,
            "net_outgoing" => Numbers::storage($row->net_outgoing ?? "0"),
            "stock_status" => StockStatus::fromLevels($quantity, $minimum)->value,
        ];

    }

}
