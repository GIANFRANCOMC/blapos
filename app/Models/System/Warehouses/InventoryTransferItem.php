<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Casts\System\{ConfigurableDecimal};
use App\Models\System\Catalogs\{Item};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class InventoryTransferItem extends Model {

    public $timestamps = false;

    protected $fillable = ["item_id", "quantity", "outbound_movement_id", "inbound_movement_id"];

    protected $casts = ["quantity" => ConfigurableDecimal::class];

    public function item(): BelongsTo {
        return $this->belongsTo(Item::class);
    }

}
