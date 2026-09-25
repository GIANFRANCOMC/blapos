<?php

namespace App\Models\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Company};
use Illuminate\Database\Eloquent\{Model};

class Role extends Model {
    protected $table = "roles";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "slug",
        "name",
        "is_full_access",
        "branch_scope_mode",
        "cash_register_scope_mode",
        "warehouse_scope_mode",
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

    public function users() {

        return $this->hasMany(User::class, "role_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function roleSubSections() {

        return $this->hasMany(RoleSubSection::class, "role_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function subSections() {

        return $this->belongsToMany(
            \App\Models\System\General\SubSection::class,
            "role_sub_sections",
            "role_id",
            "sub_section_id"
        )->wherePivot("status", "active");

    }

    public function branches() {

        return $this->belongsToMany(Branch::class, "role_branches", "role_id", "branch_id")
            ->wherePivot("status", "active");

    }

    public function cashRegisters() {

        return $this->belongsToMany(
            \App\Models\System\Finance\CashRegister::class,
            "role_cash_registers",
            "role_id",
            "cash_register_id"
        )->wherePivot("status", "active");

    }

    public function warehouses() {

        return $this->belongsToMany(
            \App\Models\System\Warehouses\Warehouse::class,
            "role_warehouses",
            "role_id",
            "warehouse_id"
        )->wherePivot("status", "active");

    }
}
