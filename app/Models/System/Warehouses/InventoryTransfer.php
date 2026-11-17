<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Enums\System\Warehouses\{InventoryTransferStatus};
use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany};

final class InventoryTransfer extends Model {

    const UPDATED_AT = null;

    protected $fillable = ["reference", "source_warehouse_id", "destination_warehouse_id", "user_id", "reason", "status", "canceled_at", "canceled_by", "cancellation_reason"];

    protected $casts = ["status" => InventoryTransferStatus::class, "canceled_at" => "datetime"];

    protected $hidden = ["source_warehouse_id", "destination_warehouse_id", "user_id", "canceled_by"];

    public function sourceWarehouse(): BelongsTo {
        return $this->belongsTo(Warehouse::class, "source_warehouse_id");
    }

    public function destinationWarehouse(): BelongsTo {
        return $this->belongsTo(Warehouse::class, "destination_warehouse_id");
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function canceledByUser(): BelongsTo {
        return $this->belongsTo(User::class, "canceled_by");
    }

    public function items(): HasMany {
        return $this->hasMany(InventoryTransferItem::class);
    }

}
