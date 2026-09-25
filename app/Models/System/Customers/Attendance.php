<?php

namespace App\Models\System\Customers;

use App\Helpers\System\{Utilities};
use App\Models\System\Devices\{BiometricDevice};
use App\Models\System\Organizations\{Branch, Company};
use Carbon\{Carbon};
use Illuminate\Database\Eloquent\{Model};

class Attendance extends Model {
    protected $table = "attendances";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "worked_hours",
        "formatted_type",
        "formatted_status",
    ];

    protected $fillable = [
        "branch_id",
        "customer_id",
        "biometric_device_id",
        "source_reference",
        "start_date",
        "end_date",
        "observation",
        "motive",
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
    public function getWorkedHoursAttribute() {

        $startDate = $this->attributes["start_date"] ?? null;
        $endDate = $this->attributes["end_date"] ?? null;

        if(!Utilities::isDefined($startDate) || !Utilities::isDefined($endDate)) {

            return 0;

        }

        $start = Carbon::parse($startDate);

        $end = Carbon::parse($endDate);

        return round($start->floatDiffInHours($end), 3);

    }

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

    public function biometricDevice() {

        return $this->belongsTo(BiometricDevice::class, "biometric_device_id", "id");

    }

    public function corrections() {

        return $this->hasMany(AttendanceCorrection::class, "attendance_id")
            ->orderByDesc("id");

    }
}
