<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo};

class InventoryMovement extends Model {

    protected $table = "inventory_movements";

    public $timestamps = false;

    protected $appends = ["reference"];

    protected $fillable = [
        "warehouse_id",
        "item_id",
        "user_id",
        "movement_type",
        "origin_type",
        "origin_id",
        "quantity_before",
        "quantity_change",
        "quantity_after",
        "unit_cost",
        "value_before",
        "value_change",
        "value_after",
        "reason",
        "metadata",
        "created_at",
    ];

    protected $casts = [
        "quantity_before" => "App\\Casts\\System\\ConfigurableDecimal",
        "quantity_change" => "App\\Casts\\System\\ConfigurableDecimal",
        "quantity_after" => "App\\Casts\\System\\ConfigurableDecimal",
        "unit_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "value_before" => "App\\Casts\\System\\ConfigurableDecimal",
        "value_change" => "App\\Casts\\System\\ConfigurableDecimal",
        "value_after" => "App\\Casts\\System\\ConfigurableDecimal",
        "metadata" => "array",
        "created_at" => "datetime",
    ];

    public function getReferenceAttribute(): ?string {

        return ($this->metadata ?? [])["reference"] ?? null;

    }

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder {

        return $query->where("warehouse_id", $warehouseId);

    }

    public function scopeForItem(Builder $query, int $itemId): Builder {

        return $query->where("item_id", $itemId);

    }

    public function scopeFromOrigin(Builder $query, string $originType, ?int $originId = null): Builder {

        return $query->where("origin_type", $originType)
            ->when($originId !== null, fn(Builder $originQuery) => $originQuery->where("origin_id", $originId));

    }

    public function warehouse(): BelongsTo {

        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class, "item_id", "id");

    }

    public function user(): BelongsTo {

        return $this->belongsTo(User::class, "user_id", "id");

    }
}
