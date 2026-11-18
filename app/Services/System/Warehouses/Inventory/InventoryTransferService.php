<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\Inventory;

use App\Enums\System\Warehouses\{InventoryTransferStatus, InventoryTransferLineStatus};
use App\Helpers\System\{Numbers, Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use App\Models\System\Warehouses\{InventoryTransfer, InventoryTransferOperation, Warehouse};
use App\Services\System\Base\{InternalCodeService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use DateTimeInterface;
use DomainException;
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\{Collection, Str};

final class InventoryTransferService {
    public static function register(array $data): array {
        $source = (int) ($data["source_warehouse_id"] ?? 0);
        $destination = (int) ($data["destination_warehouse_id"] ?? 0);
        $reason = trim((string) ($data["reason"] ?? ""));
        $items = $data["items"] ?? [];
        $status = InventoryTransferStatus::tryFrom($data["status"] ?? InventoryTransferStatus::AwaitingDispatch->value);
        if($source === $destination) throw new DomainException("Selecciona almacenes diferentes para el traslado.");
        if(!is_array($items) || !$items || count($items) > 100) throw new DomainException("Agrega entre 1 y 100 productos al traslado.");
        if($reason === "" || mb_strlen($reason) > 255) throw new DomainException("Ingresa un motivo de hasta 255 caracteres.");
        if(!$status?->canRegister()) throw new DomainException("El traslado debe iniciar Por enviar o En tránsito. La recepción se confirma con cantidades.");
        $positions = [];
        foreach($items as $item) {
            $itemId = (int) ($item["item_id"] ?? 0);
            $quantity = self::positiveQuantity($item["quantity"] ?? null);
            if(isset($positions[$itemId])) throw new DomainException("No repitas un producto en el mismo traslado.");
            $positions[$itemId] = ["item_id" => $itemId, "quantity" => $quantity, "status" => InventoryTransferLineStatus::AwaitingDispatch];
        }
        ksort($positions, SORT_NUMERIC);
        return DB::transaction(function() use ($data, $source, $destination, $reason, $positions, $status): array {
            if(Warehouse::whereIn("id", [$source, $destination])->count() !== 2
                || Item::where("type", "product")->whereKey(array_keys($positions))->count() !== count($positions)) {
                throw new DomainException("El producto o el almacén no pertenece a la empresa.");
            }
            $prefix = InternalCodeService::prefix(CompanySettingService::TRANSFER_REFERENCE_PREFIX_KEY);
            $reference = ($prefix === "" ? "" : "{$prefix}-").strtoupper(Str::random(10));
            $transfer = InventoryTransfer::create([
                "reference" => $reference, "source_warehouse_id" => $source, "destination_warehouse_id" => $destination,
                "user_id" => $data["user_id"] ?? null, "reason" => $reason,
                "status" => InventoryTransferStatus::AwaitingDispatch, "version" => 0,
            ]);
            $transfer->items()->createMany($positions);
            self::recordStatus($transfer, "registered", $transfer->user_id, $transfer->created_at);
            if($status === InventoryTransferStatus::InTransit) self::dispatch($transfer, $transfer->user_id);
            return ["id" => $transfer->id, "reference" => $reference, "status" => $transfer->status->value, "items_count" => count($positions)];
        }, 3);
    }

    public static function advance(int $id, InventoryTransferStatus $status, ?int $userId, int $expectedVersion, ?string $reason = null): void {
        if(!in_array($status, [InventoryTransferStatus::InTransit, InventoryTransferStatus::Canceled], true)) {
            throw new DomainException("Para recibir productos debes registrar una recepción con sus cantidades.");
        }
        DB::transaction(function() use ($id, $status, $userId, $expectedVersion, $reason): void {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($id);
            if($transfer->status === $status) return;
            self::expectVersion($transfer, $expectedVersion);
            if(!$transfer->status->canCancel()) throw new DomainException("El traslado ya salió del origen. Registra su recepción o resuelve las diferencias.", 409);
            if($status === InventoryTransferStatus::InTransit) {
                self::dispatch($transfer, $userId);
                return;
            }
            $transfer->update(["status" => $status, "version" => $transfer->version + 1,
                "canceled_at" => now(), "canceled_by" => $userId, "cancellation_reason" => $reason]);
            $transfer->items()->update(["status" => InventoryTransferLineStatus::Canceled->value]);
            self::recordStatus($transfer, "canceled", $userId, $transfer->canceled_at);
        }, 3);
    }

    private static function dispatch(InventoryTransfer $transfer, ?int $userId): void {
        foreach($transfer->items()->orderBy("item_id")->lockForUpdate()->get() as $line) {
            $exit = InventoryMovementService::apply([
                "warehouse_id" => $transfer->source_warehouse_id, "item_id" => $line->item_id, "user_id" => $userId,
                "movement_type" => InventoryMovementService::TYPE_EXIT, "origin_type" => InventoryMovementService::ORIGIN_TRANSFER_OUT,
                "origin_id" => $transfer->id, "quantity" => $line->quantity, "reason" => $transfer->reason,
            ]);
            $line->update(["outbound_movement_id" => $exit->id, "transit_value" => Numbers::subtract(0, $exit->value_change),
                "status" => InventoryTransferLineStatus::AwaitingReceipt]);
        }
        $transfer->update(["status" => InventoryTransferStatus::InTransit, "version" => $transfer->version + 1]);
        self::recordStatus($transfer, "dispatched", $userId);
    }

    /** Each receipt or resolution is immutable and safe to retry with the same request key. */
    public static function operate(int $id, string $kind, array $data, ?int $userId): InventoryTransferOperation {
        if(!in_array($kind, ["receipt", "return", "loss"], true)) throw new DomainException("La operación del traslado no es válida.");
        $key = $data["request_key"] ?? "";
        if(!is_string($key) || !Str::isUuid($key)) throw new DomainException("La operación necesita un identificador válido para evitar duplicados.");
        $reason = trim((string) ($data["reason"] ?? ""));
        if(mb_strlen($reason) > 255 || ($kind !== "receipt" && $reason === "")) throw new DomainException("Explica el motivo de la diferencia (hasta 255 caracteres).");
        $items = $data["items"] ?? [];
        if(!is_array($items) || !$items || count($items) > 100) throw new DomainException("Selecciona entre 1 y 100 productos con cantidad mayor que cero.");
        $quantities = [];
        foreach($items as $item) {
            $lineId = (int) ($item["id"] ?? 0);
            if(isset($quantities[$lineId])) throw new DomainException("No repitas un producto en la operación.");
            $quantities[$lineId] = self::positiveQuantity($item["quantity"] ?? null);
        }
        ksort($quantities, SORT_NUMERIC);
        $fingerprint = hash("sha256", json_encode([$kind, $reason, $quantities, (int) ($data["expected_version"] ?? -1), $userId], JSON_THROW_ON_ERROR));
        return DB::transaction(function() use ($id, $kind, $data, $userId, $key, $reason, $quantities, $fingerprint): InventoryTransferOperation {
            $transfer = InventoryTransfer::query()->lockForUpdate()->findOrFail($id);
            $existing = $transfer->operations()->where("request_key", $key)->first();
            if($existing) {
                if(!hash_equals($existing->payload_hash, $fingerprint)) throw new DomainException("Este identificador ya se usó con otra operación. Actualiza el detalle.", 409);
                return $existing;
            }
            self::expectVersion($transfer, (int) ($data["expected_version"] ?? -1));
            if(!$transfer->status->canReceive()) throw new DomainException("El traslado no tiene cantidades en tránsito para procesar.", 409);
            $lines = $transfer->items()->whereIn("id", array_keys($quantities))->orderBy("item_id")->lockForUpdate()->get();
            if($lines->count() !== count($quantities)) throw new DomainException("Un producto no pertenece a este traslado.");
            foreach($lines as $line) {
                if(!$line->outbound_movement_id || Numbers::compare($quantities[$line->id], $line->pending_quantity) > 0) {
                    throw new DomainException("La cantidad de {$line->item->name} supera lo pendiente de recibir.", 409);
                }
            }
            $operation = $transfer->operations()->create([
                "kind" => $kind, "request_key" => $key, "payload_hash" => $fingerprint,
                "user_id" => $userId, "user_name" => self::userName($userId), "reason" => $reason ?: null, "occurred_at" => now(),
            ]);
            foreach($lines as $line) {
                $quantity = $quantities[$line->id];
                $pending = $line->pending_quantity;
                // Allocate the remaining value; the final portion absorbs rounding residuals.
                $value = Numbers::compare($quantity, $pending) === 0 ? Numbers::round($line->transit_value)
                    : Numbers::proportion($line->transit_value, $quantity, $pending);
                $movement = null;
                if($kind !== "loss") {
                    $movement = InventoryMovementService::apply([
                        "warehouse_id" => $kind === "receipt" ? $transfer->destination_warehouse_id : $transfer->source_warehouse_id,
                        "item_id" => $line->item_id, "user_id" => $userId,
                        "movement_type" => InventoryMovementService::TYPE_ENTRY,
                        "origin_type" => $kind === "receipt" ? InventoryMovementService::ORIGIN_TRANSFER_IN : "transfer_return",
                        "origin_id" => $transfer->id, "quantity" => $quantity,
                        "unit_cost" => Numbers::divide($value, $quantity), "entry_value" => $value,
                        "reason" => $reason ?: $transfer->reason,
                        "metadata" => ["transfer_operation_id" => $operation->id],
                    ]);
                }
                $operation->items()->create(["inventory_transfer_item_id" => $line->id, "quantity" => $quantity,
                    "value" => $value, "movement_id" => $movement?->id]);
                $field = $kind === "receipt" ? "received_quantity" : "resolved_quantity";
                $line->{$field} = Numbers::sum([$line->{$field}, $quantity]);
                $line->transit_value = Numbers::subtract($line->transit_value, $value);
                $line->status = $line->pending_quantity <= 0
                    ? ($line->resolved_quantity > 0 ? InventoryTransferLineStatus::Resolved : InventoryTransferLineStatus::Received)
                    : ($line->received_quantity > 0 ? InventoryTransferLineStatus::PartiallyReceived : InventoryTransferLineStatus::AwaitingReceipt);
                $line->save();
            }
            $all = $transfer->items()->get();
            $complete = $all->every(fn($line) => $line->pending_quantity <= 0);
            $status = $complete
                ? ($all->contains(fn($line) => $line->resolved_quantity > 0) ? InventoryTransferStatus::ClosedWithDifferences : InventoryTransferStatus::Received)
                : ($all->contains(fn($line) => $line->received_quantity > 0) ? InventoryTransferStatus::PartiallyReceived : InventoryTransferStatus::InTransit);
            $transfer->update(["status" => $status, "version" => $transfer->version + 1]);
            self::recordStatus($transfer, match($kind) {"receipt" => "received", "return" => "returned", "loss" => "lost"}, $userId, null, $operation);
            return $operation;
        }, 3);
    }

    private static function positiveQuantity(mixed $value): float {
        if(!is_numeric($value) || !is_finite((float) $value)) throw new DomainException("Ingresa cantidades numéricas válidas.");
        $quantity = Numbers::round($value);
        if($quantity <= 0 || Numbers::compare($quantity, Numbers::maxValue()) > 0) throw new DomainException("Las cantidades deben ser mayores que cero y respetar el máximo configurado.");
        return $quantity;
    }

    private static function expectVersion(InventoryTransfer $transfer, int $version): void {
        if((int) $transfer->version !== $version) throw new DomainException("El traslado cambió mientras lo revisabas. Actualiza el detalle antes de continuar.", 409);
    }

    private static function userName(?int $id): string {
        return $id === null ? "Proceso del sistema" : (User::query()->whereKey($id)->value("name") ?? "Usuario no disponible");
    }

    private static function recordStatus(InventoryTransfer $transfer, string $action, ?int $userId, ?DateTimeInterface $at = null, ?InventoryTransferOperation $operation = null): void {
        $transfer->statusHistory()->create([
            "status" => $transfer->status, "action" => $action, "operation_id" => $operation?->id,
            "user_id" => $userId, "user_name" => $operation?->user_name ?? self::userName($userId),
            "occurred_at" => $at ?? $operation?->occurred_at ?? now(),
        ]);
    }

    public static function getUsers(?array $warehouseIds): Collection {
        $authors = InventoryTransfer::query()->select("user_id")
            ->when($warehouseIds !== null, fn(Builder $query) => self::forWarehouses($query, $warehouseIds));

        return User::query()->whereIn("id", $authors)->orderBy("name")->toBase()->get(["id", "name"]);
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
            ->when($filters["user_id"] ?? null, fn(Builder $query, $userId) => $query->where("user_id", $userId))
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
            "statusHistory",
            "operations.items.transferItem.item.measurementUnit",
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
