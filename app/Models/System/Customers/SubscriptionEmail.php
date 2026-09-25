<?php

namespace App\Models\System\Customers;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Company};
use Illuminate\Database\Eloquent\{Model};

class SubscriptionEmail extends Model {
    protected $table = "subscription_emails";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $appends = [
        "formatted_extras_json",
        "formatted_type",
        "formatted_status",
    ];

    protected $fillable = [
        "to",
        "subject",
        "body",
        "extras_json",
        "type",
        "model_id",
        "model_type",
        "status",
        "attempts",
        "max_attempts",
        "next_attempt_at",
        "sent_at",
        "failed_at",
        "last_error",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "attempts" => "integer",
        "max_attempts" => "integer",
        "next_attempt_at" => "datetime",
        "sent_at" => "datetime",
        "failed_at" => "datetime",
    ];

    // Appends
    public function getFormattedExtrasJsonAttribute() {

        return json_decode($this->attributes["extras_json"] ?? "");

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
            ["code" => "SubscriptionExpired", "label" => "Membresía vencida"],
            ["code" => "SubscriptionWelcome", "label" => "Agradecimiento por suscripción"],
        ];

        return Utilities::getValues($types, $type, $code);

    }

    public static function getStatuses($type = "all", $code = "") {

        $statuses = [
            ["code" => "pending", "label" => "Pendiente"],
            ["code" => "sent", "label" => "Enviado"],
            ["code" => "failed", "label" => "Fallido"],
        ];

        return Utilities::getValues($statuses, $type, $code);

    }

    // Relationships
}
