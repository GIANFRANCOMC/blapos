<?php

namespace App\Models\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Models\System\Warehouses\{Warehouse};
use Illuminate\Database\Eloquent\{Model};

class Branch extends Model {

    protected $table = "branches";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "company_id",
        "internal_code",
        "name",
        "address",
        "reference",
        "telephone",
        "email",
        "capacity",
        "map_url",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    // Appends
    public function getFormattedStatusAttribute() {

        $status = $this->attributes["status"] ?? null;

        return $status ? (self::getStatuses("first", $status)["label"] ?? "") : "";

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

    public function series() {

        return $this->hasMany(Serie::class, "branch_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function warehouses() {

        return $this->hasMany(Warehouse::class, "branch_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function warehousesAll() {

        return $this->hasMany(Warehouse::class, "branch_id", "id");

    }

    public function userAttendances() {

        return $this->hasMany(UserAttendance::class, "branch_id", "id");

    }

}
