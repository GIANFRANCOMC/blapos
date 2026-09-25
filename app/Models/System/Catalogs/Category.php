<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{CategoryItem};
use Illuminate\Database\Eloquent\{Builder, Model, Relations\HasMany};

class Category extends Model {
    protected $table = "categories";

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "internal_code",
        "name",
        "description",
        "sort_order",
        "is_public",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "sort_order" => "integer",
        "is_public" => "boolean",
    ];

    // Appends
    public function getFormattedStatusAttribute() {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    // Functions
    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    // Relationships
    public function scopeActive(Builder $query): Builder {

        return $query->where("status", "active");

    }

    public function items(): HasMany {

        return $this->hasMany(CategoryItem::class, "category_id", "id")
            ->whereIn("status", ["active"]);

    }
}
