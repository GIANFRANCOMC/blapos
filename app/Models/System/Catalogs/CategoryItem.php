<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Models\System\Catalogs\{Category};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

class CategoryItem extends Model {
    protected $table = "category_items";

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "category_id",
        "item_id",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
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
    public function category(): BelongsTo {

        return $this->belongsTo(Category::class, "category_id", "id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class, "item_id", "id");

    }
}
