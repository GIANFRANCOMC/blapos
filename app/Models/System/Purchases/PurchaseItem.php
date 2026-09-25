<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo};

final class PurchaseItem extends Model {

    protected $table = "purchase_items";

    protected $fillable = [
        "purchase_header_id",
        "item_id",
        "name",
        "quantity",
        "received_quantity",
        "unit_cost",
        "allocated_expense_total",
        "inventory_unit_cost",
        "subtotal",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "received_quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "unit_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "allocated_expense_total" => "App\\Casts\\System\\ConfigurableDecimal",
        "inventory_unit_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "subtotal" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    protected $appends = ["remaining_quantity"];

    public function getRemainingQuantityAttribute(): float {

        return Utilities::round(
            (float) ($this->attributes["quantity"] ?? 0)
            - (float) ($this->attributes["received_quantity"] ?? 0),
            null
        );

    }

    public function scopePendingReceipt(Builder $query): Builder {

        return $query->whereIn("status", ["pending", "partial"])
            ->whereColumn("received_quantity", "<", "quantity");

    }

    public function purchase(): BelongsTo {

        return $this->belongsTo(PurchaseHeader::class, "purchase_header_id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class, "item_id");

    }
}
