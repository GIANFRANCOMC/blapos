<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo};

final class InventoryStockAlert extends Model {
    protected $table = "inventory_stock_alerts";

    protected $fillable = [
        "warehouse_item_id",
        "quantity",
        "minimum_stock",
        "status",
        "detected_at",
        "resolved_at",
        "resolved_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "minimum_stock" => "App\\Casts\\System\\ConfigurableDecimal",
        "detected_at" => "datetime",
        "resolved_at" => "datetime",
    ];

    public function scopeOpen(Builder $query): Builder {

        return $query->where("status", "open");

    }

    public function warehouseItem(): BelongsTo {

        return $this->belongsTo(WarehouseItem::class);

    }

    public function resolvedBy(): BelongsTo {

        return $this->belongsTo(User::class, "resolved_by");

    }
}
