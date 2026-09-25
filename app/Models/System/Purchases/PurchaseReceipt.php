<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Models\System\Warehouses\{Warehouse};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany};

final class PurchaseReceipt extends Model {
    protected $table = "purchase_receipts";

    public $timestamps = false;

    protected $fillable = [
        "purchase_header_id",
        "warehouse_id",
        "reference",
        "received_at",
        "observation",
        "status",
        "created_at",
        "created_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "received_at" => "datetime",
        "created_at" => "datetime",
        "canceled_at" => "datetime",
    ];

    public function purchase(): BelongsTo {

        return $this->belongsTo(PurchaseHeader::class, "purchase_header_id");

    }

    public function warehouse(): BelongsTo {

        return $this->belongsTo(Warehouse::class, "warehouse_id");

    }

    public function items(): HasMany {

        return $this->hasMany(PurchaseReceiptItem::class, "purchase_receipt_id");

    }
}
