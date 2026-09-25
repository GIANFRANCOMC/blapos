<?php

namespace App\Models\System\General;

use App\Helpers\System\{Utilities};
use App\Models\System\Customers\{Customer};
use App\Models\System\Organizations\{Company};
use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Model};

class IdentityDocumentType extends Model {
    protected $table = "identity_document_types";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "code",
        "name",
        "is_searchable",
        "min_length",
        "max_length",
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
    public function companies() {

        return $this->hasMany(Company::class, "identity_document_type_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function customers() {

        return $this->hasMany(Customer::class, "identity_document_type_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function users() {

        return $this->hasMany(User::class, "identity_document_type_id", "id")
            ->whereIn("status", ["active"]);

    }
}
