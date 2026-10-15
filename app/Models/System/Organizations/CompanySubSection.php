<?php

namespace App\Models\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{SubSection};
use Illuminate\Database\Eloquent\{Model};

class CompanySubSection extends Model {

    protected $table = "companies_sub_sections";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "company_id",
        "sub_section_id",
        "section_order",
        "sub_section_order",
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
    public function company() {

        return $this->belongsTo(Company::class, "company_id", "id");

    }

    public function subSection() {

        return $this->belongsTo(SubSection::class, "sub_section_id", "id");

    }

}
