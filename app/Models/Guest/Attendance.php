<?php

namespace App\Models\Guest;

use App\Helpers\System\{Utilities};
use Illuminate\Database\Eloquent\{Model};

class Attendance extends Model {
    protected $table = "attendances";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_type",
        "formatted_status",
    ];

    protected $fillable = [
        "branch_id",
        "customer_id",
        "start_date",
        "end_date",
        "observation",
        "type",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    // Appends
    public function getFormattedTypeAttribute() {

        return self::getTypes("first", $this->attributes["type"] ?? "")["label"] ?? "";

    }

    public function getFormattedStatusAttribute() {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    // Functions
    public static function getTypes($type = "all", $code = "") {

        $types = [
            ["code" => "manual_form", "label" => "Manual"],
            ["code" => "qr_camera", "label" => "Cámara interna"],
            ["code" => "qr_scanner", "label" => "Escáner externo"],
            ["code" => "qr_public", "label" => "Público"],
            ["code" => "biometric", "label" => "Biométrico"],
        ];

        return Utilities::getValues($types, $type, $code);

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "En curso"],
            ["code" => "canceled", "label" => "Anulada"],
            ["code" => "inactive", "label" => "Inactiva"],
            ["code" => "finalized", "label" => "Concluida"],
            ["code" => "absent", "label" => "Ausente"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    // Relationships

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function customer() {

        return $this->belongsTo(Customer::class, "customer_id", "id");

    }
}
