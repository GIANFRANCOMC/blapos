<?php

namespace App\Models\System\General;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{CompanySubSection, RoleSubSection};
use Illuminate\Database\Eloquent\{Model};

class SubSection extends Model {

    protected $table = "sub_sections";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "section_id",
        "menu_group_id",
        "slug",
        "name",
        "description",
        "order",
        "dom_id",
        "dom_label",
        "dom_icon",
        "dom_route",
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
    public function section() {

        return $this->belongsTo(Section::class, "section_id", "id");

    }

    public function menuGroup() {

        return $this->belongsTo(MenuGroup::class, "menu_group_id", "id");

    }

    public function companiesSubSections() {

        return $this->hasMany(CompanySubSection::class, "sub_section_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function roleSubSections() {

        return $this->hasMany(RoleSubSection::class, "sub_section_id", "id")
            ->whereIn("status", ["active"]);

    }

}
