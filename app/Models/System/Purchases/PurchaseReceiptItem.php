<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Warehouses\{InventoryMovement};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class PurchaseReceiptItem extends Model {
    protected $table = "purchase_receipt_items";

    public $timestamps = false;

    protected $fillable = [
        "purchase_receipt_id",
        "purchase_item_id",
        "item_id",
        "inventory_movement_id",
        "quantity",
        "unit_cost",
        "total_cost",
        "created_at",
        "created_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "unit_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "total_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "created_at" => "datetime",
    ];

    public function receipt(): BelongsTo {

        return $this->belongsTo(PurchaseReceipt::class, "purchase_receipt_id");

    }

    public function purchaseItem(): BelongsTo {

        return $this->belongsTo(PurchaseItem::class, "purchase_item_id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class, "item_id");

    }

    public function inventoryMovement(): BelongsTo {

        return $this->belongsTo(InventoryMovement::class, "inventory_movement_id");

    }
}
