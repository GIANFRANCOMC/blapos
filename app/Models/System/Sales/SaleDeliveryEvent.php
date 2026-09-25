<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use App\Models\System\Organizations\{User};
use App\Models\System\Warehouses\{Warehouse};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany};

class SaleDeliveryEvent extends Model {

    protected $table = "sale_delivery_events";

    public $timestamps = false;

    protected $fillable = [
        "sale_delivery_id",
        "warehouse_id",
        "delivered_by",
        "delivered_at",
        "total_quantity",
        "observation",
        "status",
        "created_at",
        "created_by",
    ];

    protected $casts = [
        "total_quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "delivered_at" => "datetime",
        "created_at" => "datetime",
    ];

    public function delivery(): BelongsTo {

        return $this->belongsTo(SaleDelivery::class, "sale_delivery_id", "id");

    }

    public function warehouse(): BelongsTo {

        return $this->belongsTo(Warehouse::class, "warehouse_id", "id");

    }

    public function deliveredBy(): BelongsTo {

        return $this->belongsTo(User::class, "delivered_by", "id");

    }

    public function items(): HasMany {

        return $this->hasMany(SaleDeliveryEventItem::class, "sale_delivery_event_id", "id");

    }
}
