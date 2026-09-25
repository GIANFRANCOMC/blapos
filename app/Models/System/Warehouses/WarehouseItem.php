<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Item};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, Relations\HasMany};

class WarehouseItem extends Model {

    protected $table = "warehouse_items";

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "warehouse_id",
        "item_id",
        "quantity",
        "minimum_stock",
        "average_cost",
        "inventory_value",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "minimum_stock" => "App\\Casts\\System\\ConfigurableDecimal",
        "average_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "inventory_value" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    // Appends
    public function getFormattedStatusAttribute() {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    // Functions
    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    // Relationships
    public function scopeActive(Builder $query): Builder {

        return $query->where("status", "active");

    }

    public function scopeForStock(Builder $query, int $warehouseId, int $itemId): Builder {

        return $query->where("warehouse_id", $warehouseId)
            ->where("item_id", $itemId);

    }

    public function warehouse(): BelongsTo {

        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class, "item_id", "id");

    }

    public function stockAlerts(): HasMany {

        return $this->hasMany(InventoryStockAlert::class, "warehouse_item_id");

    }
}
