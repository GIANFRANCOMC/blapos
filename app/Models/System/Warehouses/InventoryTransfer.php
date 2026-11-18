<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Enums\System\Warehouses\{InventoryTransferStatus};
use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany};

final class InventoryTransfer extends Model {

    const UPDATED_AT = null;

    protected $fillable = ["reference", "source_warehouse_id", "destination_warehouse_id", "user_id", "reason", "status", "version", "canceled_at", "canceled_by", "cancellation_reason"];

    protected $casts = ["status" => InventoryTransferStatus::class, "canceled_at" => "datetime"];

    protected $hidden = ["source_warehouse_id", "destination_warehouse_id", "user_id", "canceled_by"];

    protected $appends = ["receiver_name", "sender_name"];

    public function getReceiverNameAttribute(): ?string {
        if(!$this->relationLoaded("operations")) return null;
        return $this->operations->where("kind", "receipt")->pluck("user_name")->unique()->implode(", ") ?: null;
    }

    public function getSenderNameAttribute(): ?string {
        if(!$this->relationLoaded("statusHistory")) return null;
        return $this->statusHistory->firstWhere("action", "dispatched")?->user_name;
    }

    public function operations(): HasMany {
        return $this->hasMany(InventoryTransferOperation::class)->orderBy("id");
    }

    public function statusHistory(): HasMany {
        return $this->hasMany(InventoryTransferStatusEvent::class)->orderBy("id");
    }

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
