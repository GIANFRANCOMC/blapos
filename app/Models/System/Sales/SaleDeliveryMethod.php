<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use Illuminate\Database\Eloquent\{Builder, Model, Relations\HasMany};

final class SaleDeliveryMethod extends Model {
    protected $table = "sale_delivery_methods";

    protected $fillable = [
        "code",
        "name",
        "description",
        "sort_order",
        "is_default",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "sort_order" => "integer",
        "is_default" => "boolean",
    ];

    public function scopeActive(Builder $query): Builder {

        return $query->where("status", "active")
            ->orderBy("sort_order")
            ->orderBy("name");

    }

    public function sales(): HasMany {

        return $this->hasMany(SaleHeader::class, "delivery_method_id");

    }
}
