<?php

namespace App\Models\System\General;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Serie};
use Illuminate\Database\Eloquent\{Model};

class DocumentType extends Model {
    protected $table = "document_types";

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
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public static function displayName(?string $name, ?string $code = null): string {

        $value = trim((string) ($name ?: $code));
        $normalized = strtoupper($value);

        if(str_contains($normalized, "BOLETA") || $normalized === "BV") {

            return "BOLETA";

        }

        if($normalized === "FA") {

            return "FACTURA";

        }

        return $normalized;

    }

    public function getNameAttribute($value): string {

        return self::displayName($value, $this->attributes["code"] ?? null);

    }

    public function setNameAttribute($value): void {

        $this->attributes["name"] = self::displayName($value, $this->attributes["code"] ?? null);

    }

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
    public function series() {

        return $this->hasMany(Serie::class, "document_type_id", "id")
            ->whereIn("status", ["active"]);

    }
}
