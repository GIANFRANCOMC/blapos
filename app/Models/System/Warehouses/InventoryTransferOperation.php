<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use Illuminate\Database\Eloquent\{Model, Relations\HasMany};

final class InventoryTransferOperation extends Model {
    public $timestamps = false;
    protected $guarded = ["id", "inventory_transfer_id"];
    protected $casts = ["occurred_at" => "datetime"];
    protected $hidden = ["payload_hash", "inventory_transfer_id", "user_id"];
    protected $appends = ["label"];

    public function items(): HasMany {
        return $this->hasMany(InventoryTransferOperationItem::class);
    }

    public function getLabelAttribute(): string {
        return match($this->kind) {
            "receipt" => "Recepción",
            "return" => "Devolución al origen",
            "loss" => "Pérdida justificada",
        };
    }
}
