<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use App\Helpers\System\{Utilities};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\HasMany};

class Brand extends Model {
    protected $table = "brands";

    protected $fillable = [
        "internal_code",
        "name",
        "description",
        "logo_path",
        "origin_country_code",
        "website_url",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $appends = [
        "formatted_status",
    ];

    public function getFormattedStatusAttribute(): string {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    public static function getStatuses($type = "all", $code = "") {

        return Utilities::getValues([
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ], $type, $code);

    }

    public function scopeActive(Builder $query): Builder {

        return $query->where("status", "active");

    }

    public function products(): HasMany {

        return $this->hasMany(Item::class, "brand_id", "id")
            ->where("type", "product");

    }
}
