<?php

declare(strict_types=1);

namespace App\Models\System\Catalogs;

use Illuminate\Database\Eloquent\{Model};

class RecipeToppingComponent extends Model {
    protected $table = "recipe_topping_components";

    protected $fillable = [
        "recipe_topping_id",
        "item_id",
        "quantity",
        "waste_percentage",
        "note",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "waste_percentage" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function topping() {

        return $this->belongsTo(RecipeTopping::class, "recipe_topping_id", "id");

    }

    public function item() {

        return $this->belongsTo(Item::class, "item_id", "id");

    }
}
