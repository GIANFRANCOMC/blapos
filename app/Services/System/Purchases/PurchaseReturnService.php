<?php

declare(strict_types=1);

namespace App\Services\System\Purchases;

use App\Helpers\System\{Utilities};
use App\Models\System\Purchases\{PurchaseHeader, PurchaseItem, PurchaseReturn, PurchaseReturnItem};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use DomainException;
use Illuminate\Support\Facades\{DB};

final class PurchaseReturnService {
    public static function create(int $purchaseId, int $userId, array $data): PurchaseReturn {

        return DB::transaction(function() use ($purchaseId, $userId, $data) {

            $purchase = PurchaseHeader::query()
                ->whereIn("status", ["partial", "received"])
                ->lockForUpdate()
                ->find($purchaseId);

            if(!$purchase) {

                throw new DomainException("La compra no tiene mercadería recibida disponible para devolución.");

            }

            if((int) $purchase->warehouse_id !== (int) $data["warehouse_id"]) {

                throw new DomainException("La devolución debe salir del almacén receptor de la compra.");

            }

            $return = PurchaseReturn::create([
                "purchase_header_id" => $purchaseId,
                "purchase_receipt_id" => $data["purchase_receipt_id"] ?? null,
                "warehouse_id" => $data["warehouse_id"],
                "reference" => "DVP-".Utilities::generateCode(10),
                "returned_at" => $data["returned_at"] ?? now(),
                "reason" => $data["reason"],
                "status" => "confirmed",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            foreach($data["items"] as $line) {

                $purchaseItem = PurchaseItem::query()
                    ->where("purchase_header_id", $purchaseId)
                    ->lockForUpdate()
                    ->find((int) $line["purchase_item_id"]);

                if(!$purchaseItem) {

                    throw new DomainException("Uno de los productos no pertenece a la compra.");

                }

                $previouslyReturned = (float) PurchaseReturnItem::query()
                    ->join("purchase_returns", "purchase_returns.id", "=", "purchase_return_items.purchase_return_id")
                    ->where("purchase_return_items.purchase_item_id", $purchaseItem->id)
                    ->where("purchase_returns.status", "confirmed")
                    ->sum("purchase_return_items.quantity");

                $quantity = Utilities::round((float) $line["quantity"]);
                $available = Utilities::round((float) $purchaseItem->received_quantity - $previouslyReturned);

                if($quantity > $available) {

                    throw new DomainException("La devolución supera la cantidad recibida disponible.");

                }

                $movement = InventoryMovementService::apply([
                    "warehouse_id" => $data["warehouse_id"],
                    "item_id" => $purchaseItem->item_id,
                    "user_id" => $userId,
                    "movement_type" => InventoryMovementService::TYPE_EXIT,
                    "origin_type" => InventoryMovementService::ORIGIN_SUPPLIER_RETURN,
                    "origin_id" => $return->id,
                    "quantity" => $quantity,
                    "unit_cost" => (float) $purchaseItem->unit_cost,
                    "reason" => $data["reason"],
                    "reference" => $return->reference,
                ]);

                PurchaseReturnItem::create([
                    "purchase_return_id" => $return->id,
                    "purchase_item_id" => $purchaseItem->id,
                    "item_id" => $purchaseItem->item_id,
                    "inventory_movement_id" => $movement->id,
                    "quantity" => $quantity,
                    "unit_cost" => $purchaseItem->unit_cost,
                    "total_cost" => Utilities::round($quantity * (float) $purchaseItem->unit_cost),
                    "created_at" => now(),
                ]);

            }

            return $return->load(["purchase.supplier", "warehouse", "items.item", "items.movement"]);

        });

    }
}
