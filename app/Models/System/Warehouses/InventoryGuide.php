<?php

declare(strict_types=1);

namespace App\Models\System\Warehouses;

use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, Relations\HasMany};

final class InventoryGuide extends Model {
    protected $fillable = [
        "warehouse_id",
        "number",
        "guide_type",
        "issue_date",
        "reason",
        "reference",
        "status",
        "confirmed_at",
        "confirmed_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "issue_date" => "date:Y-m-d",
        "confirmed_at" => "datetime",
        "canceled_at" => "datetime",
    ];

    public function scopeConfirmed(Builder $query): Builder {

        return $query->where("status", "confirmed");

    }

    public function warehouse(): BelongsTo {

        return $this->belongsTo(Warehouse::class);

    }

    public function items(): HasMany {

        return $this->hasMany(InventoryGuideItem::class);

    }

    public function confirmedBy(): BelongsTo {

        return $this->belongsTo(User::class, "confirmed_by");

    }
}
