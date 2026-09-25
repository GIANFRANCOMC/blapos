<?php

declare(strict_types=1);

namespace App\Services\System\Purchases;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Purchases\{PurchaseHeader, PurchaseItem, PurchasePayment, PurchaseReceipt, PurchaseReceiptItem, PurchaseTax, Supplier};
use App\Services\System\Finance\{CommercialCreditAccountService, CommercialDocumentSettlementService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use App\Services\System\Warehouses\StockManagement\{StockManagementService};
use DomainException;
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\{Str};

final class PurchaseService {
    private static function allocateExpenses(array $details, array $expenses, int $companyId): array {

        $allocations = collect($details)
            ->mapWithKeys(fn($detail) => [(int) $detail["item_id"] => 0.0])
            ->all();

        foreach($expenses as $expense) {

            $amount = Utilities::round((float) ($expense["amount"] ?? 0), null, $companyId);

            if($amount <= 0) {

                continue;

            }

            $method = (string) ($expense["allocation_method"] ?? "value");

            $weights = collect($details)->mapWithKeys(function($detail) use ($method) {

                $weight = match ($method) {
                    "quantity" => (float) $detail["quantity"],
                    "equal" => 1.0,
                    default => (float) $detail["quantity"] * (float) $detail["unit_cost"]
                };

                return [(int) $detail["item_id"] => max(0, $weight)];

            });

            $denominator = (float) $weights->sum();

            if($denominator <= 0) {

                throw new DomainException("No se pudo distribuir uno de los gastos de compra.");

            }

            $distributed = 0.0;

            $lastItemId = (int) $weights->keys()->last();

            foreach($weights as $itemId => $weight) {

                $allocated = (int) $itemId === $lastItemId
                    ? Utilities::round($amount - $distributed, null, $companyId)
                    : Utilities::round($amount * ((float) $weight / $denominator), null, $companyId);

                $allocations[(int) $itemId] += $allocated;
                $distributed += $allocated;

            }

        }

        return $allocations;

    }

    private static function generateReference(int $companyId): string {

        do {

            $reference = "COM-".strtoupper(Str::random(10));

        } while(PurchaseHeader::query()
            ->where("reference", $reference)
            ->exists());

        return $reference;

    }

    public static function getFilteredQuery(int $companyId, array $filters = [], ?int $userId = null): Builder {

        $query = PurchaseHeader::query()
            ->with([
                "supplier:id,name,document_number",
                "warehouse:id,name",
                "currency:id,code,sign",
                "items.item:id,internal_code,barcode,name",
                "taxes",
                "payments",
            ]);

        $warehouseIds = $userId === null
            ? null
            : \App\Services\System\Base\CompanyReferenceDataService::for($companyId, $userId)->allowedWarehouseIds();

        if($warehouseIds !== null) {

            $query->whereIn("warehouse_id", $warehouseIds);

        }

        $word = trim((string) ($filters["word"] ?? ""));

        $hasDocumentSeriesColumn = Schema::hasColumn("purchase_headers", "document_series");

        if($word !== "") {

            $query->where(function($query) use ($word, $hasDocumentSeriesColumn) {

                $query->where("document_number", "like", "%{$word}%")
                    ->orWhereHas("supplier", fn($query) => $query->where("name", "like", "%{$word}%")
                        ->orWhere("document_number", "like", "%{$word}%")
                    )
                    ->orWhereHas("items.item", fn($query) => $query->where("name", "like", "%{$word}%")
                        ->orWhere("internal_code", "like", "%{$word}%")
                        ->orWhere("barcode", "like", "%{$word}%")
                    );

                if($hasDocumentSeriesColumn) {

                    $query->orWhere("document_series", "like", "%{$word}%");

                }

            });

        }

        if(($filters["status"] ?? null) === "pending_receipt") {

            $query->pendingReceipt();

        }elseif(!empty($filters["status"])) {

            $query->where("status", $filters["status"]);

        }

        return $query->orderByDesc("id");

    }

    public static function create(
        int $companyId,
        int $userId,
        array $data
    ): PurchaseHeader {

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $supplier = Supplier::query()
                ->whereKey((int) $data["supplier_id"])
                ->firstOrFail();

            $warehouse = StockManagementService::validateWarehouse(
                (int) $data["warehouse_id"],
                $companyId
            );

            if(!$warehouse || $supplier->status !== "active") {

                throw new DomainException("El proveedor o el almacén no está disponible.");

            }

            $documentNumber = trim((string) ($data["document_number"] ?? ""));

            $documentSeries = trim((string) ($data["document_series"] ?? ""));
            $hasDocumentSeriesColumn = Schema::hasColumn("purchase_headers", "document_series");

            if($documentNumber !== "") {

                $documentQuery = PurchaseHeader::query()
                    ->where("supplier_id", $supplier->id)
                    ->where("document_type", $data["document_type"])
                    ->where("document_number", $documentNumber)
                    ->where("status", "!=", "canceled");

                if($hasDocumentSeriesColumn) {

                    $documentQuery->where("document_series", $documentSeries ?: null);

                }

                if($documentQuery->exists()) {

                    throw new DomainException(
                        "Ya existe un documento de compra activo con esa serie y número para el proveedor."
                    );

                }

            }

            $itemIds = collect($data["items"])->pluck("item_id")->map(fn($id) => (int) $id);

            $items = Item::query()
                ->where("type", "product")
                ->whereIn("id", $itemIds)
                ->get()
                ->keyBy("id");

            if($items->count() !== $itemIds->unique()->count()) {

                throw new DomainException("Uno de los productos no pertenece a la empresa.");

            }

            $subtotal = collect($data["items"])->sum(fn($item) => Utilities::round((float) $item["quantity"] * (float) $item["unit_cost"], null, $companyId)
            );

            $selectedTaxIds = collect($data["taxes"] ?? [])
                ->pluck("tax_id")
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $selectedTaxQuantities = collect($data["taxes"] ?? [])
                ->filter(fn($tax) => !empty($tax["tax_id"]))
                ->mapWithKeys(fn($tax) => [(int) $tax["tax_id"] => (float) ($tax["quantity"] ?? 1)])
                ->all();

            $taxLines = CommercialDocumentSettlementService::taxes(
                $companyId,
                "purchase",
                (float) $subtotal,
                $userId,
                $selectedTaxIds,
                $selectedTaxQuantities
            );

            $tax = Utilities::round((float) $taxLines->sum("amount"), null, $companyId);
            $expenses = is_array($data["expenses"] ?? null) ? $data["expenses"] : [];
            $expenseTotal = Utilities::round((float) collect($expenses)->sum("amount"), null, $companyId);
            $allocatedExpenses = self::allocateExpenses($data["items"], $expenses, $companyId);
            $total = Utilities::round($subtotal + $tax + $expenseTotal, null, $companyId);
            $defaultPaymentModality = (string) CompanySettingService::value(
                $companyId,
                CompanySettingService::PURCHASES,
                "default_payment_modality",
                CommercialCreditAccountService::PAID_NOW
            );

            $paymentModality = CommercialCreditAccountService::normalizePaymentModality(
                $data["payment_modality"] ?? null,
                $defaultPaymentModality
            );

            $installmentExtraPercentage = $paymentModality === CommercialCreditAccountService::INSTALLMENTS
                ? (float) CompanySettingService::value(
                    $companyId,
                    CompanySettingService::PURCHASES,
                    "installment_extra_percentage",
                    0
                )
                : 0.0;

            $installmentExtraAmount = Utilities::round($total * ($installmentExtraPercentage / 100), null, $companyId);
            $total = Utilities::round($total + $installmentExtraAmount, null, $companyId);
            $paymentLines = CommercialDocumentSettlementService::payments(
                $companyId,
                "purchase",
                (float) $total,
                $data["payments"] ?? [],
                $userId,
                $paymentModality === CommercialCreditAccountService::PAID_NOW
            );

            $paidAmount = Utilities::round((float) $paymentLines->sum("amount"), null, $companyId);
            $balanceDue = Utilities::round($total - $paidAmount, null, $companyId);
            $paymentStatus = CommercialCreditAccountService::paymentStatus((float) $total, (float) $paidAmount, (int) $companyId);

            $purchaseData = [
                "supplier_id" => $supplier->id,
                "warehouse_id" => $warehouse->id,
                "currency_id" => (int) $data["currency_id"],
                "document_type" => $data["document_type"],
                "reference" => self::generateReference($companyId),
                "document_number" => $documentNumber ?: null,
                "issue_date" => $data["issue_date"],
                "expected_date" => $data["expected_date"] ?? null,
                "due_date" => $data["due_date"] ?? null,
                "approval_status" => $data["approval_status"] ?? "approved",
                "approved_by" => ($data["approval_status"] ?? "approved") === "approved" ? $userId : null,
                "approved_at" => ($data["approval_status"] ?? "approved") === "approved" ? now() : null,
                "delivery_mode" => $data["delivery_mode"] ?? "immediate",
                "payment_modality" => $paymentModality,
                "installment_extra_percentage" => $installmentExtraPercentage,
                "installment_extra_amount" => $installmentExtraAmount,
                "subtotal" => $subtotal,
                "tax" => $tax,
                "expense_total" => $expenseTotal,
                "total" => $total,
                "paid_amount" => $paidAmount,
                "balance_due" => $balanceDue,
                "payment_status" => $paymentStatus,
                "observation" => $data["observation"] ?? null,
                "status" => "confirmed",
                "created_at" => now(),
                "created_by" => $userId,
            ];

            if($hasDocumentSeriesColumn) {

                $purchaseData["document_series"] = $documentSeries ?: null;

            }

            $purchase = PurchaseHeader::create($purchaseData);

            if($taxLines->isNotEmpty()) {

                PurchaseTax::insert($taxLines
                    ->map(fn($tax) => ["purchase_header_id" => $purchase->id] + $tax)
                    ->all());

            }

            if($paymentLines->isNotEmpty()) {

                PurchasePayment::insert($paymentLines
                    ->map(fn($payment) => ["purchase_header_id" => $purchase->id] + $payment)
                    ->all());

            }

            CommercialCreditAccountService::createPayable(
                $purchase,
                $paymentLines,
                (int) ($data["installment_count"] ?? 1),
                $data["first_due_date"] ?? null,
                $userId
            );

            if(!empty($expenses)) {

                \App\Models\System\Purchases\PurchaseExpense::insert(collect($expenses)
                    ->map(fn($expense) => [
                        "purchase_header_id" => $purchase->id,
                        "expense_type" => $expense["expense_type"],
                        "name" => $expense["name"],
                        "amount" => $expense["amount"],
                        "allocation_method" => $expense["allocation_method"] ?? "value",
                        "note" => $expense["note"] ?? null,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ])->all());

            }

            foreach($data["items"] as $detail) {

                $item = $items->get((int) $detail["item_id"]);
                $quantity = Utilities::round((float) $detail["quantity"], null, $companyId);
                $unitCost = Utilities::round((float) $detail["unit_cost"], null, $companyId);
                $allocatedExpense = Utilities::round((float) ($allocatedExpenses[$item->id] ?? 0), null, $companyId);
                $inventoryUnitCost = $quantity > 0
                    ? Utilities::round($unitCost + ($allocatedExpense / $quantity), null, $companyId)
                    : $unitCost;

                PurchaseItem::create([
                    "purchase_header_id" => $purchase->id,
                    "item_id" => $item->id,
                    "name" => $item->name,
                    "quantity" => $quantity,
                    "received_quantity" => 0,
                    "unit_cost" => $unitCost,
                    "allocated_expense_total" => $allocatedExpense,
                    "inventory_unit_cost" => $inventoryUnitCost,
                    "subtotal" => Utilities::round($quantity * $unitCost, null, $companyId),
                    "status" => "pending",
                    "created_at" => now(),
                    "created_by" => $userId,
                ]);

            }

            if(($data["delivery_mode"] ?? "immediate") === "immediate"
                && ($data["approval_status"] ?? "approved") === "approved") {

                $purchase->load("items");

                self::receive($companyId, $purchase->id, $userId, [
                    "received_at" => now()->toDateTimeString(),
                    "observation" => "Entrega inmediata registrada al crear la compra.",
                    "items" => $purchase->items
                        ->map(fn($item) => [
                            "purchase_item_id" => (int) $item->id,
                            "quantity" => (float) $item->quantity,
                        ])
                        ->all(),
                ]);

            }

            return self::find($companyId, $purchase->id);

        });

    }

    public static function receive(
        int $companyId,
        int $purchaseId,
        int $userId,
        array $data
    ): PurchaseReceipt {

        return DB::transaction(function() use ($companyId, $purchaseId, $userId, $data) {

            $purchase = PurchaseHeader::query()
                ->whereKey($purchaseId)
                ->lockForUpdate()
                ->firstOrFail();

            if(!in_array($purchase->status, ["confirmed", "partial"], true)) {

                throw new DomainException("La compra no admite nuevas recepciones.");

            }

            if($purchase->approval_status !== "approved") {

                throw new DomainException("La orden debe aprobarse antes de registrar una recepción.");

            }

            $purchase->load("items");

            $receipt = PurchaseReceipt::create([
                "purchase_header_id" => $purchase->id,
                "warehouse_id" => $purchase->warehouse_id,
                "reference" => "REC-".strtoupper(Str::random(10)),
                "received_at" => $data["received_at"],
                "observation" => $data["observation"] ?? null,
                "status" => "received",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            foreach($data["items"] as $receivedItem) {

                $purchaseItem = $purchase->items
                    ->firstWhere("id", (int) $receivedItem["purchase_item_id"]);

                if(!$purchaseItem) {

                    throw new DomainException("Uno de los productos no pertenece a la compra.");

                }

                $quantity = Utilities::round((float) $receivedItem["quantity"], null, $companyId);

                $remaining = Utilities::round((float) $purchaseItem->quantity - (float) $purchaseItem->received_quantity, null, $companyId);

                if($quantity <= 0 || $quantity > $remaining) {

                    throw new DomainException(
                        "La cantidad recibida de {$purchaseItem->name} supera el saldo pendiente."
                    );

                }

                $movement = InventoryMovementService::apply([
                    "warehouse_id" => (int) $purchase->warehouse_id,
                    "item_id" => (int) $purchaseItem->item_id,
                    "user_id" => $userId,
                    "movement_type" => InventoryMovementService::TYPE_ENTRY,
                    "origin_type" => InventoryMovementService::ORIGIN_PURCHASE,
                    "origin_id" => (int) $receipt->id,
                    "quantity" => $quantity,
                    "unit_cost" => (float) $purchaseItem->inventory_unit_cost,
                    "reason" => "Recepción de compra.",
                    "reference" => $receipt->reference,
                    "metadata" => [
                        "purchase_header_id" => (int) $purchase->id,
                        "purchase_item_id" => (int) $purchaseItem->id,
                    ],
                ]);

                PurchaseReceiptItem::create([
                    "purchase_receipt_id" => $receipt->id,
                    "purchase_item_id" => $purchaseItem->id,
                    "item_id" => $purchaseItem->item_id,
                    "inventory_movement_id" => $movement->id,
                    "quantity" => $quantity,
                    "unit_cost" => $purchaseItem->inventory_unit_cost,
                    "total_cost" => Utilities::round($quantity * (float) $purchaseItem->inventory_unit_cost, null, $companyId),
                    "created_at" => now(),
                    "created_by" => $userId,
                ]);

                $receivedQuantity = Utilities::round((float) $purchaseItem->received_quantity + $quantity, null, $companyId);
                $purchaseItem->update([
                    "received_quantity" => $receivedQuantity,
                    "status" => $receivedQuantity >= (float) $purchaseItem->quantity
                        ? "received"
                        : "partial",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ]);

            }

            $purchase->load("items");

            $allReceived = $purchase->items->every(fn($item) => (float) $item->received_quantity >= (float) $item->quantity
            );

            $purchase->update([
                "status" => $allReceived ? "received" : "partial",
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);

            return $receipt->load("items.item");

        });

    }

    public static function cancel(int $companyId, int $purchaseId, int $userId): PurchaseHeader {

        return DB::transaction(function() use ($companyId, $purchaseId, $userId) {

            $restoreStockPolicyEnabled = (bool) CompanySettingService::value(
                $companyId,
                CompanySettingService::INVENTORY_POLICIES,
                "restore_stock_on_purchase_cancellation",
                false
            );

            $purchase = PurchaseHeader::query()
                ->whereKey($purchaseId)
                ->with(["items", "receipts.items"])
                ->lockForUpdate()
                ->firstOrFail();

            if($purchase->status === "canceled") {

                throw new DomainException("La compra ya está anulada.");

            }

            if($purchase->items->contains(fn($item) => (float) $item->received_quantity > 0)
                && !$restoreStockPolicyEnabled) {

                throw new DomainException(
                    "La compra tiene mercadería recibida. Registra una devolución a proveedor desde Inventario o activa la política de reversa automática al anular compras."
                );

            }

            $hadReceipts = $purchase->items->contains(fn($item) => (float) $item->received_quantity > 0);

            if($restoreStockPolicyEnabled) {

                foreach($purchase->receipts as $receipt) {

                    if($receipt->status !== "received") {

                        continue;

                    }

                    foreach($receipt->items as $receiptItem) {

                        InventoryMovementService::apply([
                            "warehouse_id" => (int) $receipt->warehouse_id,
                            "item_id" => (int) $receiptItem->item_id,
                            "user_id" => $userId,
                            "movement_type" => InventoryMovementService::TYPE_EXIT,
                            "origin_type" => InventoryMovementService::ORIGIN_PURCHASE_CANCELLATION,
                            "origin_id" => (int) $purchase->id,
                            "quantity" => (float) $receiptItem->quantity,
                            "unit_cost" => (float) $receiptItem->unit_cost,
                            "reason" => "Reversa automática por anulación de compra.",
                            "reference" => $purchase->reference,
                            "metadata" => [
                                "purchase_header_id" => (int) $purchase->id,
                                "purchase_receipt_id" => (int) $receipt->id,
                                "purchase_receipt_item_id" => (int) $receiptItem->id,
                            ],
                        ]);

                    }

                    $receipt->update([
                        "status" => "canceled",
                        "canceled_at" => now(),
                        "canceled_by" => $userId,
                    ]);

                }

            }

            $purchase->update([
                "status" => "canceled",
                "updated_at" => now(),
                "updated_by" => $userId,
                "canceled_at" => now(),
                "canceled_by" => $userId,
            ]);
            PurchaseItem::where("purchase_header_id", $purchase->id)->update([
                "status" => "canceled",
                "received_quantity" => $restoreStockPolicyEnabled ? 0 : DB::raw("received_quantity"),
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);

            $result = self::find($companyId, $purchase->id);
            $result->setAttribute("inventory_reverted_on_cancellation", $restoreStockPolicyEnabled && $hadReceipts);
            $result->setAttribute("had_inventory_receipts", $hadReceipts);

            return $result;

        });

    }

    public static function approve(int $companyId, int $purchaseId, int $userId): PurchaseHeader {

        return DB::transaction(function() use ($companyId, $purchaseId, $userId) {

            $purchase = PurchaseHeader::query()
                ->where("status", "confirmed")
                ->lockForUpdate()
                ->findOrFail($purchaseId);

            if($purchase->approval_status !== "approved") {

                $purchase->update([
                    "approval_status" => "approved",
                    "approved_by" => $userId,
                    "approved_at" => now(),
                    "updated_by" => $userId,
                    "updated_at" => now(),
                ]);

            }

            return self::find($companyId, $purchaseId);

        });

    }

    public static function find(int $companyId, int $purchaseId): PurchaseHeader {

        return PurchaseHeader::query()
            ->with([
                "supplier",
                "warehouse.branch",
                "currency",
                "items.item",
                "taxes",
                "payments",
                "expenses",
                "receipts.items.item",
                "returns.items.item",
            ])
            ->findOrFail($purchaseId);

    }
}
