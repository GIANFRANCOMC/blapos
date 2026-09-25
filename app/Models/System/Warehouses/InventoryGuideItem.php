<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Models\System\Catalogs\{Item};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class InventoryGuideItem extends Model {
    protected $fillable = [
        "inventory_guide_id",
        "item_id",
        "inventory_movement_id",
        "quantity",
        "unit_cost",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "unit_cost" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function guide(): BelongsTo {

        return $this->belongsTo(InventoryGuide::class, "inventory_guide_id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class);

    }

    public function movement(): BelongsTo {

        return $this->belongsTo(InventoryMovement::class, "inventory_movement_id");

    }
}
