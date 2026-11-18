<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Casts\System\{ConfigurableDecimal};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class InventoryTransferOperationItem extends Model {
    public $timestamps = false;
    protected $guarded = ["id", "inventory_transfer_operation_id"];
    protected $casts = ["quantity" => ConfigurableDecimal::class, "value" => ConfigurableDecimal::class];
    protected $hidden = ["inventory_transfer_operation_id"];

    public function transferItem(): BelongsTo {
        return $this->belongsTo(InventoryTransferItem::class, "inventory_transfer_item_id");
    }
}
