<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Enums\System\Warehouses\{InventoryTransferStatus};
use Illuminate\Database\Eloquent\{Model};

final class InventoryTransferStatusEvent extends Model {

    public $timestamps = false;

    protected $fillable = ["status", "action", "operation_id", "user_id", "user_name", "occurred_at"];

    protected $casts = ["status" => InventoryTransferStatus::class, "occurred_at" => "datetime"];

    protected $hidden = ["inventory_transfer_id", "user_id"];

    protected $appends = ["label", "tone"];

    public function getLabelAttribute(): string {
        return match($this->action) {
            "received" => $this->status === InventoryTransferStatus::Received ? "Recepción completa registrada" : "Recepción parcial registrada",
            "returned" => "Devolución al origen registrada",
            "lost" => "Pérdida justificada registrada",
            "legacy_sent" => "Envío anterior sin salida de stock",
            default => $this->status->label(),
        };
    }

    public function getToneAttribute(): string {
        return $this->status->tone();
    }

}
