<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use Illuminate\Database\Eloquent\{Model};

class RecipeDishOption extends Model {
    protected $table = "recipe_dish_options";

    protected $fillable = [
        "recipe_dish_id",
        "name",
        "description",
        "max_portions",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function recipeDish() {

        return $this->belongsTo(RecipeDish::class, "recipe_dish_id", "id");

    }

    public function components() {

        return $this->hasMany(RecipeDishOptionComponent::class, "recipe_dish_option_id", "id")
            ->where("status", "active");

    }
}
