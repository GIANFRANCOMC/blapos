<?php

namespace App\Models\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{Currency, IdentityDocumentType};
use Illuminate\Database\Eloquent\{Model};

class Company extends Model {
    protected $table = "companies";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "slug",
        "internal_code",
        "identity_document_type_id",
        "document_number",
        "legal_name",
        "commercial_name",
        "currency_id",
        "tagline",
        "description",
        "address",
        "telephone",
        "email",
        "token_api_misc",
        "logotype",
        "combinationmark",
        "logomark",
        "login_image",
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
    public function identityDocumentType() {

        return $this->belongsTo(IdentityDocumentType::class, "identity_document_type_id", "id");

    }

    public function currency() {

        return $this->belongsTo(Currency::class, "currency_id", "id");

    }

    public function branches() {

        return $this->hasMany(Branch::class, "company_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function settings() {

        return $this->hasMany(CompanySetting::class, "company_id", "id");

    }

    public function companySubSections() {

        return $this->hasMany(CompanySubSection::class, "company_id", "id")
            ->whereIn("status", ["active"]);

    }

    public function socialsMedia() {

        return $this->hasMany(CompanySocialMedia::class, "company_id", "id")
            ->whereIn("status", ["active"]);

    }
}
