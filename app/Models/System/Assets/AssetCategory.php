<?php

declare(strict_types=1);

namespace App\Models\System\Assets;

use Illuminate\Database\Eloquent\{Model};

final class AssetCategory extends Model {
    protected $table = "asset_categories";

    protected $fillable = ["name", "description", "status", "created_by", "updated_by"];

    public function assets() {

        return $this->hasMany(Asset::class, "asset_category_id");

    }
}
