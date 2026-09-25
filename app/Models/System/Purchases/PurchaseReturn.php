<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Models\System\Warehouses\{Warehouse};
use Illuminate\Database\Eloquent\{Model};

final class PurchaseReturn extends Model {
    protected $table = "purchase_returns";

    public $timestamps = false;

    protected $fillable = [
        "purchase_header_id", "purchase_receipt_id", "warehouse_id",
        "reference", "returned_at", "reason", "status", "created_at", "created_by",
        "canceled_at", "canceled_by",
    ];

    protected $casts = ["returned_at" => "datetime", "canceled_at" => "datetime"];

    public function purchase() {

        return $this->belongsTo(PurchaseHeader::class, "purchase_header_id");

    }

    public function receipt() {

        return $this->belongsTo(PurchaseReceipt::class, "purchase_receipt_id");

    }

    public function warehouse() {

        return $this->belongsTo(Warehouse::class, "warehouse_id");

    }

    public function items() {

        return $this->hasMany(PurchaseReturnItem::class, "purchase_return_id");

    }
}
