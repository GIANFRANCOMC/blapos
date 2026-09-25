<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use Illuminate\Database\Eloquent\{Model};

final class BusinessIndustry extends Model {
    protected $table = "business_industries";

    protected $fillable = [
        "slug",
        "name",
        "description",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function moduleSets() {

        return $this->hasMany(BusinessIndustryModuleSet::class, "business_industry_id");

    }
}
