<?php

namespace App\Models\System\Assets;

use App\Helpers\System\{Utilities};
use App\Models\System\General\{Currency};
use App\Models\System\Organizations\{Branch};
use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Model};

class AssetAssignment extends Model {
    protected $table = "asset_assignments";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_status",
    ];

    protected $fillable = [
        "user_id",
        "branch_id",
        "asset_id",
        "currency_id",
        "quantity",
        "acquisition_value",
        "acquisition_date",
        "note",
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
            ["code" => "active", "label" => "Disponible"],
            ["code" => "maintenance", "label" => "En mantenimiento"],
            ["code" => "retired", "label" => "Retirado"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    // Relationships
    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function asset() {

        return $this->belongsTo(Asset::class, "asset_id", "id");

    }

    public function currency() {

        return $this->belongsTo(Currency::class, "currency_id", "id");

    }
}
