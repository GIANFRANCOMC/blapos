<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{Branch, Company};
use App\Models\System\Warehouses\{InventoryMovement, Warehouse};
use Illuminate\Database\Eloquent\{Model};

class CashSessionInventoryCount extends Model {
    protected $table = "cash_session_inventory_counts";

    protected $fillable = [
        "branch_id",
        "cash_session_id",
        "warehouse_id",
        "item_id",
        "inventory_movement_id",
        "system_quantity",
        "counted_quantity",
        "difference_quantity",
        "observation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "system_quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "counted_quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "difference_quantity" => "App\\Casts\\System\\ConfigurableDecimal",
    ];


    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function cashSession() {

        return $this->belongsTo(CashSession::class, "cash_session_id", "id");

    }

    public function warehouse() {

        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");

    }

    public function item() {

        return $this->belongsTo(Item::class, "item_id", "id");

    }

    public function inventoryMovement() {

        return $this->belongsTo(InventoryMovement::class, "inventory_movement_id", "id");

    }
}
