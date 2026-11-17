<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\Inventory;

use App\Enums\System\Warehouses\{InventoryTransferStatus};
use App\Helpers\System\{Numbers, Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Warehouses\{InventoryTransfer, Warehouse};
use App\Services\System\Base\{InternalCodeService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use DomainException;
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class InventoryTransferService {

    public static function register(array $data): array {
        $source = (int) ($data["source_warehouse_id"] ?? 0);
        $destination = (int) ($data["destination_warehouse_id"] ?? 0);
        $reason = trim((string) ($data["reason"] ?? ""));
        $items = $data["items"] ?? [];
        $status = InventoryTransferStatus::tryFrom($data["status"] ?? InventoryTransferStatus::Accepted->value);

        if($source === $destination) throw new DomainException("Selecciona almacenes diferentes para el traslado.");
        if(!is_array($items) || !$items) throw new DomainException("Agrega al menos un producto al traslado.");
        if(count($items) > 100) throw new DomainException("Puedes trasladar hasta 100 productos por operación.");
        if($reason === "") throw new DomainException("El motivo del traslado es obligatorio.");
        if(!$status || $status === InventoryTransferStatus::Canceled) throw new DomainException("El estado inicial del traslado no es válido.");

        $positions = [];
        foreach($items as $item) {
            $itemId = (int) ($item["item_id"] ?? 0);
            $quantity = Numbers::round($item["quantity"] ?? 0);
            if($quantity <= 0) throw new DomainException("Todas las cantidades deben ser mayores que cero.");
            if(isset($positions[$itemId])) throw new DomainException("No repitas un producto en el mismo traslado.");

            $positions[$itemId] = ["item_id" => $itemId, "quantity" => $quantity];
        }

        return DB::transaction(function() use ($data, $source, $destination, $reason, $positions, $status): array {
            if(Warehouse::whereIn("id", [$source, $destination])->count() !== 2
                || Item::where("type", "product")->whereKey(array_keys($positions))->count() !== count($positions)) {
                throw new DomainException("El producto o el almacén no pertenece a la empresa.");
            }

            $prefix = InternalCodeService::prefix(CompanySettingService::TRANSFER_REFERENCE_PREFIX_KEY);
            $reference = ($prefix === "" ? "" : "{$prefix}-").strtoupper(Str::random(10));
            $transfer = InventoryTransfer::create([
                "reference" => $reference,
                "source_warehouse_id" => $source,
                "destination_warehouse_id" => $destination,
                "user_id" => $data["user_id"] ?? null,
                "reason" => $reason,
                "status" => $status,
            ]);
            $transfer->setRelation("items", $transfer->items()->createMany($positions));
            $movements = $status === InventoryTransferStatus::Accepted ? self::postMovements($transfer, $transfer->user_id) : [];

            return [
                "id" => $transfer->id,
                "reference" => $reference,
                "status" => $status->value,
                "items_count" => count($positions),
                "movements" => $movements,
            ];
        });
    }

    public static function advance(
        int $id,
        InventoryTransferStatus $status,
        int $userId,
        ?InventoryTransferStatus $expectedStatus = null,
        ?string $cancellationReason = null
    ): void {
        DB::transaction(function() use ($id, $status, $userId, $expectedStatus, $cancellationReason): void {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($id);
            if($transfer->status === $status) return;

            if($status === InventoryTransferStatus::Canceled) {
                if(!$transfer->status->canCancel()) {
                    throw new DomainException("Solo se pueden cancelar traslados pendientes o enviados.");
                }
                if($transfer->status !== $expectedStatus) {
                    throw new DomainException("El traslado cambió de estado. Vuelve a consultar el detalle.");
                }

                $transfer->update([
                    "status" => $status,
                    "canceled_at" => now(),
                    "canceled_by" => $userId,
                    "cancellation_reason" => $cancellationReason,
                ]);
                return;
            }

            if($transfer->status->next() !== $status) {
                throw new DomainException("El traslado cambió de estado. Vuelve a consultar el detalle.");
            }

            if($status === InventoryTransferStatus::Accepted) {
                self::postMovements($transfer->load(["items" => fn($items) => $items->orderBy("id")]), $userId);
            }
            $transfer->update(["status" => $status]);
        }, 3);
    }

    private static function postMovements(InventoryTransfer $transfer, ?int $userId): array {
        $movements = [];
        foreach($transfer->items as $line) {
            $data = [
                "user_id" => $userId,
                "origin_id" => $transfer->id,
                "reason" => $transfer->reason,
                "item_id" => (int) $line->item_id,
                "quantity" => $line->getRawOriginal("quantity"),
            ];
            $exit = InventoryMovementService::apply([
                "warehouse_id" => $transfer->source_warehouse_id,
                "movement_type" => InventoryMovementService::TYPE_EXIT,
                "origin_type" => InventoryMovementService::ORIGIN_TRANSFER_OUT,
            ] + $data)->setRelation("transfer", $transfer);
            $entry = InventoryMovementService::apply([
                "warehouse_id" => $transfer->destination_warehouse_id,
                "movement_type" => InventoryMovementService::TYPE_ENTRY,
                "origin_type" => InventoryMovementService::ORIGIN_TRANSFER_IN,
                "unit_cost" => $exit->unit_cost,
                "entry_value" => -(float) $exit->value_change,
            ] + $data)->setRelation("transfer", $transfer);

            $line->update([
                "quantity" => $entry->quantity_change,
                "outbound_movement_id" => $exit->id,
                "inbound_movement_id" => $entry->id,
            ]);
            $movements[] = ["item_id" => $line->item_id, "exit" => $exit, "entry" => $entry];
        }

        return $movements;
    }

    public static function query(array $filters): Builder {
        $warehouseIds = $filters["allowed_warehouse_ids"] ?? null;
        $warehouseId = ($filters["warehouse_id"] ?? "all") === "all" ? null : (int) $filters["warehouse_id"];
        $selectedWarehouses = $warehouseIds === null ? [$warehouseId] : array_intersect([$warehouseId], $warehouseIds);
        $productSearch = trim((string) ($filters["product_search"] ?? ""));
        $referenceSearch = trim((string) ($filters["reference"] ?? ""));
        $referencePattern = "%".strtr($referenceSearch, ["!" => "!!", "%" => "!%", "_" => "!_"])."%";
        $itemId = $filters["item_id"] ?? null;

        $warehouse = fn($query) => $query->select(["id", "branch_id", "name"])
            ->with("branch:id,name")
            ->when($warehouseIds !== null, fn($query) => $query->whereIn("id", $warehouseIds));

        $query = InventoryTransfer::query()
            ->with(["sourceWarehouse" => $warehouse, "destinationWarehouse" => $warehouse, "user:id,name"])
            ->withCount("items")
            ->when($warehouseIds !== null, fn(Builder $query) => self::forWarehouses($query, $warehouseIds))
            ->when($warehouseId !== null, fn(Builder $query) => self::forWarehouses($query, $selectedWarehouses))
            ->when($filters["transfer_status"] ?? null, fn(Builder $query, $status) => $query->where("status", $status))
            ->when($referenceSearch !== "", fn(Builder $query) => $query->whereRaw("reference LIKE ? ESCAPE '!'", [$referencePattern]))
            ->when($itemId || $productSearch !== "", fn(Builder $query) => $query->whereHas("items", function(Builder $line) use ($itemId, $productSearch) {
                $line->when($itemId, fn(Builder $line) => $line->where("item_id", $itemId))
                    ->when($productSearch !== "", fn(Builder $line) => $line->whereHas("item", fn(Builder $item) => $item->where(
                        fn(Builder $item) => $item->where("name", "like", "%{$productSearch}%")
                            ->orWhere("internal_code", "like", "%{$productSearch}%")
                            ->orWhere("barcode", "like", "%{$productSearch}%")
                    )));
            }))
            ->when($filters["date_from"] ?? null, fn(Builder $query, $date) => $query->where("created_at", ">=", Utilities::startOfDay($date)))
            ->when($filters["date_to"] ?? null, fn(Builder $query, $date) => $query->where("created_at", "<=", Utilities::endOfDay($date)))
            ->orderByDesc("created_at")
            ->orderByDesc("id");

        foreach(["source_warehouse_id", "destination_warehouse_id"] as $column) {
            $id = (int) ($filters[$column] ?? 0);
            if($id > 0) {
                $query->whereIn($column, $warehouseIds === null ? [$id] : array_intersect([$id], $warehouseIds));
            }
        }

        return $query;
    }

    public static function withItems(Builder $query): Builder {
        return $query->with([
            "canceledByUser:id,name",
            "items" => fn($items) => $items->orderBy("id"),
            "items.item:id,name,internal_code,barcode,measurement_unit_id",
            "items.item.measurementUnit:id,abbreviation",
        ]);
    }

    private static function forWarehouses(Builder $query, array $ids): Builder {
        return $query->where(fn(Builder $query) => $query->whereIn("source_warehouse_id", $ids)
            ->orWhereIn("destination_warehouse_id", $ids));
    }

}
