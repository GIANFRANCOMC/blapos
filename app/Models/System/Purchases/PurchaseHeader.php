<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{Currency};
use App\Models\System\Organizations\{User};
use App\Models\System\Warehouses\{Warehouse};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, Relations\HasMany, Relations\HasOne};

final class PurchaseHeader extends Model {
    protected $table = "purchase_headers";

    protected $fillable = [
        "supplier_id",
        "warehouse_id",
        "currency_id",
        "document_type",
        "document_series",
        "reference",
        "document_number",
        "issue_date",
        "expected_date",
        "due_date",
        "approval_status",
        "approved_by",
        "approved_at",
        "delivery_mode",
        "payment_modality",
        "installment_extra_percentage",
        "installment_extra_amount",
        "subtotal",
        "tax",
        "expense_total",
        "total",
        "paid_amount",
        "balance_due",
        "payment_status",
        "observation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "issue_date" => "date:Y-m-d",
        "expected_date" => "date:Y-m-d",
        "due_date" => "date:Y-m-d",
        "approved_at" => "datetime",
        "subtotal" => "App\\Casts\\System\\ConfigurableDecimal",
        "tax" => "App\\Casts\\System\\ConfigurableDecimal",
        "installment_extra_percentage" => "App\\Casts\\System\\ConfigurableDecimal",
        "installment_extra_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "expense_total" => "App\\Casts\\System\\ConfigurableDecimal",
        "total" => "App\\Casts\\System\\ConfigurableDecimal",
        "paid_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "balance_due" => "App\\Casts\\System\\ConfigurableDecimal",
        "canceled_at" => "datetime",
    ];

    protected $appends = ["formatted_status", "formatted_document_type", "formatted_delivery_mode", "formatted_payment_modality", "formatted_payment_status", "receipt_progress"];

    public function getFormattedStatusAttribute(): string {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    public function getFormattedDocumentTypeAttribute(): string {

        return self::getDocumentTypes("first", $this->attributes["document_type"] ?? "")["label"] ?? "";

    }

    public function getFormattedDeliveryModeAttribute(): string {

        return self::getDeliveryModes("first", $this->attributes["delivery_mode"] ?? "")["label"] ?? "";

    }

    public function getFormattedPaymentModalityAttribute(): string {

        return self::getPaymentModalities("first", $this->attributes["payment_modality"] ?? "")["label"] ?? "";

    }

    public function getFormattedPaymentStatusAttribute(): string {

        return self::getPaymentStatuses("first", $this->attributes["payment_status"] ?? "")["label"] ?? "";

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "confirmed", "label" => "Pendiente de recepción"],
            ["code" => "partial", "label" => "Recepción parcial"],
            ["code" => "received", "label" => "Recibida"],
            ["code" => "canceled", "label" => "Anulada"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    public static function getDocumentTypes($type = "all", $code = "") {

        $types = [
            ["code" => "order", "label" => "BOLETA"],
            ["code" => "invoice", "label" => "FACTURA"],
        ];

        return Utilities::getValues($types, $type, $code);

    }

    public static function getDeliveryModes($type = "all", $code = "") {

        $modes = [
            ["code" => "immediate", "label" => "Recepción inmediata"],
            ["code" => "pending", "label" => "Recepción pendiente"],
        ];

        return Utilities::getValues($modes, $type, $code);

    }

    public static function getPaymentModalities($type = "all", $code = "") {

        $modalities = [
            ["code" => "paid_now", "label" => "Pago al momento"],
            ["code" => "cash_on_delivery", "label" => "Pago contra entrega"],
            ["code" => "installments", "label" => "Pago en cuotas"],
        ];

        return Utilities::getValues($modalities, $type, $code);

    }

    public static function getPaymentStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "unpaid", "label" => "Pendiente"],
            ["code" => "partial", "label" => "Parcial"],
            ["code" => "paid", "label" => "Pagado"],
            ["code" => "overpaid", "label" => "Sobrepagado"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    public function getReceiptProgressAttribute(): float {

        if(!$this->relationLoaded("items")) {

            return 0;

        }

        $ordered = (float) $this->items->sum("quantity");

        $received = (float) $this->items->sum("received_quantity");

        return $ordered > 0
            ? Utilities::round(($received / $ordered) * 100)
            : 0;

    }

    public function scopePendingReceipt(Builder $query): Builder {

        return $query->whereIn("status", ["confirmed", "partial"])
            ->where("approval_status", "approved");

    }

    public function supplier(): BelongsTo {

        return $this->belongsTo(Supplier::class, "supplier_id");

    }

    public function warehouse(): BelongsTo {

        return $this->belongsTo(Warehouse::class, "warehouse_id");

    }

    public function currency(): BelongsTo {

        return $this->belongsTo(Currency::class, "currency_id");

    }

    public function approvedBy(): BelongsTo {

        return $this->belongsTo(User::class, "approved_by");

    }

    public function items(): HasMany {

        return $this->hasMany(PurchaseItem::class, "purchase_header_id");

    }

    public function receipts(): HasMany {

        return $this->hasMany(PurchaseReceipt::class, "purchase_header_id");

    }

    public function taxes(): HasMany {

        return $this->hasMany(PurchaseTax::class, "purchase_header_id")
            ->whereIn("status", ["active"]);

    }

    public function payments(): HasMany {

        return $this->hasMany(PurchasePayment::class, "purchase_header_id")
            ->whereIn("status", ["active"]);

    }

    public function accountPayable(): HasOne {

        return $this->hasOne(PurchaseAccountPayable::class, "purchase_header_id");

    }

    public function expenses(): HasMany {

        return $this->hasMany(PurchaseExpense::class, "purchase_header_id");

    }

    public function returns(): HasMany {

        return $this->hasMany(PurchaseReturn::class, "purchase_header_id");

    }
}
