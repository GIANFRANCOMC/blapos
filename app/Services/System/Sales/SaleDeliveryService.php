<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

use App\Helpers\System\{Utilities};
use App\Models\System\Sales\{SaleBody, SaleDelivery, SaleDeliveryEvent, SaleDeliveryEventItem, SaleDeliveryItem, SaleHeader};
use App\Models\System\Warehouses\{Warehouse};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use DomainException;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

final class SaleDeliveryService {
    public static function createPendingForSale(SaleHeader $saleHeader, $saleBodies, ?int $warehouseId, int $userId): ?SaleDelivery {

        if($saleHeader->delivery_status !== "pending") {

            return null;

        }

        $productBodies = collect($saleBodies)
            ->filter(fn($body) => $body instanceof SaleBody && $body->type === "product" && $body->status === "active")
            ->values();

        if($productBodies->isEmpty()) {

            return null;

        }

        $totalQuantity = Utilities::round((float) $productBodies->sum("quantity"));

        $delivery = SaleDelivery::create([
            "sale_header_id" => (int) $saleHeader->id,
            "warehouse_id" => $warehouseId,
            "total_quantity" => $totalQuantity,
            "delivered_quantity" => 0,
            "pending_quantity" => $totalQuantity,
            "status" => "pending",
            "observation" => $saleHeader->delivery_observation,
            "created_at" => now(),
            "created_by" => $userId,
        ]);

        foreach($productBodies as $body) {

            SaleDeliveryItem::create([
                "sale_delivery_id" => (int) $delivery->id,
                "sale_body_id" => (int) $body->id,
                "item_id" => (int) $body->item_id,
                "quantity_ordered" => (float) $body->quantity,
                "quantity_delivered" => 0,
                "quantity_pending" => (float) $body->quantity,
                "status" => "pending",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

        }

        return $delivery;

    }

    public static function queryPending(int $companyId, array $filters = [], ?int $userId = null): Builder {

        $query = SaleDelivery::query()
            ->whereIn("status", ["pending", "partial"])
            ->whereHas("saleHeader", fn($sale) => $sale
                ->where("status", "active")
                ->whereIn("delivery_status", ["pending", "partial"]))
            ->with([
                "saleHeader.serie.documentType",
                "saleHeader.serie.branch",
                "saleHeader.holder",
                "saleHeader.deliveryMethod",
                "warehouse.branch",
                "items.saleBody.currency",
                "items.item:id,internal_code,barcode,name",
                "events.warehouse:id,name",
                "events.deliveredBy:id,name",
                "events.items.item:id,name,internal_code,barcode",
            ]);

        $allowedWarehouseIds = null;

        if($userId !== null) {

            $user = \App\Models\System\Organizations\User::query()
                ->find($userId);

            $allowedWarehouseIds = $user ? AccessScopeService::allowedIds($user, AccessScopeService::WAREHOUSE) : [];

        }

        if($allowedWarehouseIds !== null) {

            $query->where(function($query) use ($allowedWarehouseIds) {

                $query->whereNull("warehouse_id")
                    ->orWhereIn("warehouse_id", $allowedWarehouseIds);

            });

        }

        if(!empty($filters["warehouse_id"])) {

            $query->where("warehouse_id", (int) $filters["warehouse_id"]);

        }

        if(!empty($filters["delivery_status"])) {

            $query->where("status", $filters["delivery_status"]);

        }

        if(!empty($filters["branch_id"])) {

            $query->whereHas("saleHeader.serie", fn($serie) => $serie->where("branch_id", (int) $filters["branch_id"]));

        }

        if(!empty($filters["holder_id"])) {

            $query->whereHas("saleHeader", fn($sale) => $sale->where("holder_id", (int) $filters["holder_id"]));

        }

        $search = trim((string) ($filters["search"] ?? ""));

        if($search !== "") {

            $query->where(function($query) use ($search) {

                $query->whereHas("saleHeader.holder", function($holder) use ($search) {

                    $holder->where("name", "like", "%{$search}%")
                        ->orWhere("document_number", "like", "%{$search}%");

                })
                    ->orWhereHas("saleHeader", fn($sale) => $sale->where("sequential", "like", "%{$search}%"))
                    ->orWhereHas("items.item", function($item) use ($search) {

                        $item->where("name", "like", "%{$search}%")
                            ->orWhere("internal_code", "like", "%{$search}%")
                            ->orWhere("barcode", "like", "%{$search}%");

                    });

            });

        }

        return $query;

    }

    public static function paginatePending(
        int $companyId,
        array $filters = [],
        int $perPage = 15,
        ?int $userId = null
    ): LengthAwarePaginator {

        return self::queryPending($companyId, $filters, $userId)
            ->orderBy("status")
            ->orderBy("id")
            ->paginate($perPage);

    }

    public static function deliver(SaleDelivery $delivery, array $data, int $companyId, int $userId): SaleDelivery {

        return DB::transaction(function() use ($delivery, $data, $companyId, $userId) {

            $delivery = SaleDelivery::query()
                ->whereKey($delivery->id)
                ->lockForUpdate()
                ->firstOrFail();

            if(!in_array($delivery->status, ["pending", "partial"], true)) {

                throw new DomainException("Esta entrega ya no tiene productos pendientes.");

            }

            $warehouseId = (int) ($data["warehouse_id"] ?? $delivery->warehouse_id);

            $warehouse = Warehouse::query()
                ->with("branch")
                ->whereKey($warehouseId)
                ->where("status", "active")
                ->whereHas("branch", fn($branch) => $branch->where("status", "active"))
                ->first();

            if(!$warehouse) {

                throw new DomainException("Selecciona un almacén activo para registrar la entrega.");

            }

            $itemsPayload = collect($data["items"] ?? [])
                ->filter(fn($item) => (float) ($item["quantity"] ?? 0) > 0)
                ->keyBy(fn($item) => (int) ($item["sale_delivery_item_id"] ?? 0));

            if($itemsPayload->isEmpty()) {

                throw new DomainException("Indica al menos una cantidad a entregar.");

            }

            $deliveryItems = SaleDeliveryItem::query()
                ->where("sale_delivery_id", $delivery->id)
                ->whereIn("id", $itemsPayload->keys())
                ->with("saleBody")
                ->lockForUpdate()
                ->get()
                ->keyBy("id");

            $totalDeliveredNow = 0.0;
            $event = SaleDeliveryEvent::create([
                "sale_delivery_id" => (int) $delivery->id,
                "warehouse_id" => (int) $warehouse->id,
                "delivered_by" => $userId,
                "delivered_at" => $data["delivered_at"] ?? now(),
                "total_quantity" => 0,
                "observation" => $data["observation"] ?? null,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            foreach($itemsPayload as $itemId => $payload) {

                $deliveryItem = $deliveryItems->get((int) $itemId);

                if(!$deliveryItem || $deliveryItem->status === "delivered") {

                    continue;

                }

                $quantity = Utilities::round((float) ($payload["quantity"] ?? 0), null, $companyId);

                $pending = Utilities::round((float) $deliveryItem->quantity_pending, null, $companyId);

                if($quantity <= 0) {

                    continue;

                }

                if($quantity > $pending) {

                    throw new DomainException("La cantidad a entregar supera el pendiente de {$deliveryItem->saleBody?->name}.");

                }

                $movement = SaleService::applyInventoryExit(
                    $warehouse,
                    $deliveryItem->saleBody,
                    [
                        "item_id" => (int) $deliveryItem->item_id,
                        "quantity" => $quantity,
                        "type" => "product",
                        "extras" => json_decode((string) $deliveryItem->saleBody?->extras, true) ?: [],
                    ],
                    $userId,
                    InventoryMovementService::ORIGIN_SALE_DELIVERY,
                    "Salida generada por entrega pendiente de venta.",
                    [
                        "sale_header_id" => (int) $delivery->sale_header_id,
                        "sale_delivery_id" => (int) $delivery->id,
                        "sale_delivery_event_id" => (int) $event->id,
                    ]
                );

                $delivered = Utilities::round((float) $deliveryItem->quantity_delivered + $quantity, null, $companyId);
                $newPending = Utilities::round((float) $deliveryItem->quantity_ordered - $delivered, null, $companyId);

                $deliveryItem->update([
                    "quantity_delivered" => $delivered,
                    "quantity_pending" => max(0, $newPending),
                    "status" => $newPending <= 0 ? "delivered" : "partial",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ]);

                SaleDeliveryEventItem::create([
                    "sale_delivery_event_id" => (int) $event->id,
                    "sale_delivery_item_id" => (int) $deliveryItem->id,
                    "sale_body_id" => (int) $deliveryItem->sale_body_id,
                    "item_id" => (int) $deliveryItem->item_id,
                    "inventory_movement_id" => $movement?->id,
                    "quantity" => $quantity,
                    "created_at" => now(),
                ]);

                $totalDeliveredNow += $quantity;

            }

            if($totalDeliveredNow <= 0) {

                $event->delete();
                throw new DomainException("No se registró ninguna cantidad entregada.");

            }

            $event->update([
                "total_quantity" => Utilities::round($totalDeliveredNow, null, $companyId),
            ]);

            self::refreshDeliveryStatus($delivery, $companyId, $userId, (int) $warehouse->id);

            return $delivery->fresh([
                "saleHeader.serie.documentType",
                "saleHeader.serie.branch",
                "saleHeader.holder",
                "warehouse.branch",
                "items.saleBody.currency",
                "items.item",
                "events.deliveredBy",
                "events.items.item",
            ]);

        });

    }

    public static function cancelForSale(SaleHeader $saleHeader, int $companyId, int $userId): void {

        $delivery = SaleDelivery::query()
            ->where("sale_header_id", (int) $saleHeader->id)
            ->whereIn("status", ["pending", "partial"])
            ->first();

        if(!$delivery) {

            return;

        }

        $delivery->items()->whereIn("status", ["pending", "partial"])->update([
            "status" => "canceled",
            "updated_at" => now(),
            "updated_by" => $userId,
        ]);

        $delivery->update([
            "status" => "canceled",
            "updated_at" => now(),
            "updated_by" => $userId,
            "canceled_at" => now(),
            "canceled_by" => $userId,
        ]);

    }

    private static function refreshDeliveryStatus(SaleDelivery $delivery, int $companyId, int $userId, int $warehouseId): void {

        $items = SaleDeliveryItem::query()
            ->where("sale_delivery_id", (int) $delivery->id)
            ->get();

        $total = Utilities::round((float) $items->sum("quantity_ordered"), null, $companyId);
        $delivered = Utilities::round((float) $items->sum("quantity_delivered"), null, $companyId);
        $pending = Utilities::round((float) $items->sum("quantity_pending"), null, $companyId);
        $status = $pending <= 0 ? "delivered" : ($delivered > 0 ? "partial" : "pending");

        $delivery->update([
            "warehouse_id" => $warehouseId,
            "total_quantity" => $total,
            "delivered_quantity" => $delivered,
            "pending_quantity" => max(0, $pending),
            "status" => $status,
            "last_delivered_at" => now(),
            "last_delivered_by" => $userId,
            "updated_at" => now(),
            "updated_by" => $userId,
        ]);

        SaleHeader::query()
            ->whereKey((int) $delivery->sale_header_id)
            ->update([
                "delivery_status" => $status,
                "delivered_at" => $status === "delivered" ? now() : null,
                "delivered_by" => $status === "delivered" ? $userId : null,
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);

    }
}
