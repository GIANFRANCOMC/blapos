<?php

namespace App\Models\Guest;

use App\Helpers\System\{Utilities};
use Illuminate\Database\Eloquent\{Model};

class Currency extends Model {

    protected $table = "currencies";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "code",
        "sign",
        "singular_name",
        "plural_name",
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
    public function items() {

        return $this->hasMany(Item::class, "currency_id", "id")
            ->whereIn("status", ["active"]);

    }

}
