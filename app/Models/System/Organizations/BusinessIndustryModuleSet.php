<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use App\Models\System\General\{SubSection};
use Illuminate\Database\Eloquent\{Model};

final class BusinessIndustryModuleSet extends Model {
    protected $table = "business_industry_module_sets";

    protected $fillable = [
        "business_industry_id",
        "sub_section_id",
        "is_enabled_by_default",
        "reason",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "is_enabled_by_default" => "boolean",
    ];

    public function subSection() {

        return $this->belongsTo(SubSection::class, "sub_section_id");

    }
}
