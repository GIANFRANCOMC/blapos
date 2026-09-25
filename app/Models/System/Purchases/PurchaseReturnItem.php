<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Warehouses\{InventoryMovement};
use Illuminate\Database\Eloquent\{Model};

final class PurchaseReturnItem extends Model {
    protected $table = "purchase_return_items";

    public $timestamps = false;

    protected $fillable = [
        "purchase_return_id", "purchase_item_id", "item_id",
        "inventory_movement_id", "quantity", "unit_cost", "total_cost", "created_at",
    ];

    protected $casts = ["quantity" => "App\\Casts\\System\\ConfigurableDecimal", "unit_cost" => "App\\Casts\\System\\ConfigurableDecimal", "total_cost" => "App\\Casts\\System\\ConfigurableDecimal"];

    public function item() {

        return $this->belongsTo(Item::class, "item_id");

    }

    public function movement() {

        return $this->belongsTo(InventoryMovement::class, "inventory_movement_id");

    }
}
