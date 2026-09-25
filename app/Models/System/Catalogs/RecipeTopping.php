<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{Currency};
use App\Models\System\Organizations\{Company};
use Illuminate\Database\Eloquent\{Model};

class RecipeTopping extends Model {
    protected $table = "recipe_toppings";

    protected $appends = ["formatted_status"];

    protected $fillable = [
        "currency_id",
        "item_id",
        "name",
        "description",
        "price",
        "max_quantity",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "price" => "App\\Casts\\System\\ConfigurableDecimal",
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

    public function currency() {

        return $this->belongsTo(Currency::class, "currency_id", "id");

    }

    public function item() {

        return $this->belongsTo(Item::class, "item_id", "id");

    }

    public function components() {

        return $this->hasMany(RecipeToppingComponent::class, "recipe_topping_id", "id")
            ->where("status", "active");

    }
}
