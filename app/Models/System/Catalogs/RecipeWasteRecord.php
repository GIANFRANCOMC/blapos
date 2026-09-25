<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use App\Models\System\Organizations\{Company, User};
use App\Models\System\Warehouses\{InventoryMovement, Warehouse};
use Illuminate\Database\Eloquent\{Model};

final class RecipeWasteRecord extends Model {
    protected $table = "recipe_waste_records";

    public $timestamps = false;

    protected $fillable = [
        "recipe_dish_id",
        "warehouse_id",
        "item_id",
        "inventory_movement_id",
        "quantity",
        "unit_cost",
        "total_cost",
        "reason",
        "occurred_at",
        "created_at",
        "created_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "unit_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "total_cost" => "App\\Casts\\System\\ConfigurableDecimal",
        "occurred_at" => "datetime",
        "created_at" => "datetime",
    ];

    public function recipe() {

        return $this->belongsTo(RecipeDish::class, "recipe_dish_id");

    }

    public function warehouse() {

        return $this->belongsTo(Warehouse::class, "warehouse_id");

    }

    public function item() {

        return $this->belongsTo(Item::class, "item_id");

    }

    public function inventoryMovement() {

        return $this->belongsTo(InventoryMovement::class, "inventory_movement_id");

    }

    public function createdBy() {

        return $this->belongsTo(User::class, "created_by");

    }
}
