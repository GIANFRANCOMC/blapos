<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Casts\System\{ConfigurableDecimal};
use App\Enums\System\Warehouses\{InventoryTransferLineStatus};
use App\Helpers\System\{Numbers};
use App\Models\System\Catalogs\{Item};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class InventoryTransferItem extends Model {

    public $timestamps = false;

    protected $fillable = ["item_id", "quantity", "outbound_movement_id", "received_quantity", "resolved_quantity", "transit_value", "status"];

    protected $casts = ["quantity" => ConfigurableDecimal::class, "received_quantity" => ConfigurableDecimal::class, "resolved_quantity" => ConfigurableDecimal::class, "transit_value" => ConfigurableDecimal::class, "status" => InventoryTransferLineStatus::class];

    protected $appends = ["sent_quantity", "pending_quantity", "status_label", "status_tone"];

    public function getSentQuantityAttribute(): float {
        return $this->outbound_movement_id ? Numbers::round($this->quantity) : 0.0;
    }

    public function getPendingQuantityAttribute(): float {
        return Numbers::subtract($this->sent_quantity, Numbers::sum([$this->received_quantity, $this->resolved_quantity]));
    }

    public function getStatusLabelAttribute(): string { return $this->status->label(); }
    public function getStatusToneAttribute(): string { return $this->status->tone(); }

    public function item(): BelongsTo {
        return $this->belongsTo(Item::class);
    }

}
