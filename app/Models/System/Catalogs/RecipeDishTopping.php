<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use Illuminate\Database\Eloquent\{Model};

class RecipeDishTopping extends Model {
    protected $table = "recipe_dish_toppings";

    protected $fillable = [
        "recipe_dish_id",
        "recipe_topping_id",
        "is_default",
        "min_quantity",
        "max_quantity",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "is_default" => "boolean",
        "min_quantity" => "integer",
        "max_quantity" => "integer",
    ];

    public function recipeDish() {

        return $this->belongsTo(RecipeDish::class, "recipe_dish_id", "id");

    }

    public function topping() {

        return $this->belongsTo(RecipeTopping::class, "recipe_topping_id", "id");

    }
}
