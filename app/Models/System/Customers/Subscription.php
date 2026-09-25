<?php

namespace App\Models\System\Customers;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Branch, Company};
use App\Models\System\Sales\{SaleBody, SaleHeader};
use Carbon\{Carbon};
use Illuminate\Database\Eloquent\{Model};

class Subscription extends Model {
    protected $table = "subscriptions";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_duration",
        "formatted_type",
        "formatted_status",
        "remaining_days",
        "remaining_time_label",
    ];

    protected $fillable = [
        "branch_id",
        "sale_header_id",
        "sale_body_id",
        "renewed_from_id",
        "customer_id",
        "duration_type",
        "duration_value",
        "start_date",
        "end_date",
        "set_end_of_day",
        "force",
        "attendance_limit_per_day",
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

    public function getFormattedDurationAttribute(): string {

        if(Utilities::isDefined($this->duration_type) && Utilities::isDefined($this->duration_value)) {

            $prop = $this->duration_value > 1 ? "plural" : "label";
            $durationType = self::getDurationTypes("first", $this->attributes["duration_type"] ?? "")[$prop] ?? "";

            return "{$this->duration_value} {$durationType}";

        }

        return "";

    }

    public function getFormattedTypeAttribute(): string {

        return self::getTypes("first", $this->attributes["type"] ?? "")["label"] ?? "";

    }

    public function getFormattedStatusAttribute(): string {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    public function getRemainingDaysAttribute(): ?int {

        $endDate = $this->attributes["end_date"] ?? null;

        if(!Utilities::isDefined($endDate)) {

            return null;

        }

        return now()->startOfDay()->diffInDays(Carbon::parse($endDate)->startOfDay(), false);

    }

    public function getRemainingTimeLabelAttribute(): string {

        $remainingDays = $this->remaining_days;

        if($remainingDays === null) {

            return "";

        }

        if($remainingDays < 0) {

            $days = abs($remainingDays);

            return $days === 1 ? "Venció hace 1 día" : "Venció hace {$days} días";

        }

        if($remainingDays === 0) {

            return "Vence hoy";

        }

        return $remainingDays === 1 ? "Falta 1 día" : "Faltan {$remainingDays} días";

    }

    public static function getDurationTypes($type = "all", $code = "") {

        $types = [
            ["code" => "hour", "label" => "Hora", "plural" => "Horas"],
            ["code" => "day", "label" => "Día", "plural" => "Días"],
            ["code" => "today", "label" => "Rutina", "plural" => "Rutinas"],
            ["code" => "month", "label" => "Mes", "plural" => "Meses"],
            ["code" => "year", "label" => "Año", "plural" => "Años"],
        ];

        return Utilities::getValues($types, $type, $code);

    }

    public static function getTypes($type = "all", $code = "") {

        $types = [
            ["code" => "sale", "label" => "Venta"],
            ["code" => "manual", "label" => "Manual"],
        ];

        return Utilities::getValues($types, $type, $code);

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "active", "label" => "Vigente"],
            ["code" => "inactive", "label" => "Vencida"],
            ["code" => "canceled", "label" => "Anulada"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }


    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function saleHeader() {

        return $this->belongsTo(SaleHeader::class, "sale_header_id", "id");

    }

    public function saleBody() {

        return $this->belongsTo(SaleBody::class, "sale_body_id", "id");

    }

    public function customer() {

        return $this->belongsTo(Customer::class, "customer_id", "id");

    }

    public function renewedFrom() {

        return $this->belongsTo(self::class, "renewed_from_id", "id");

    }

    public function renewals() {

        return $this->hasMany(self::class, "renewed_from_id", "id");

    }
}
