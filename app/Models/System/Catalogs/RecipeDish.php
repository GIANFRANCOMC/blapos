<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Company};
use Illuminate\Database\Eloquent\{Model};

class RecipeDish extends Model {
    protected $table = "recipe_dishes";

    protected $appends = [
        "formatted_status",
        "components_count",
        "toppings_count",
        "options_count",
    ];

    protected $fillable = [
        "item_id",
        "yield_quantity",
        "waste_percentage",
        "preparation_notes",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "yield_quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "waste_percentage" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function getFormattedStatusAttribute(): string {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    public function getComponentsCountAttribute(): int {

        return $this->relationLoaded("components")
            ? $this->components->count()
            : (int) $this->components()->count();

    }

    public function getToppingsCountAttribute(): int {

        return $this->relationLoaded("dishToppings")
            ? $this->dishToppings->count()
            : (int) $this->dishToppings()->count();

    }

    public function getOptionsCountAttribute(): int {

        return $this->relationLoaded("options")
            ? $this->options->count()
            : (int) $this->options()->count();

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }


    public function item() {

        return $this->belongsTo(Item::class, "item_id", "id");

    }

    public function components() {

        return $this->hasMany(RecipeDishComponent::class, "recipe_dish_id", "id")
            ->where("status", "active");

    }

    public function dishToppings() {

        return $this->hasMany(RecipeDishTopping::class, "recipe_dish_id", "id")
            ->where("status", "active");

    }

    public function options() {

        return $this->hasMany(RecipeDishOption::class, "recipe_dish_id", "id")
            ->where("status", "active");

    }
}
