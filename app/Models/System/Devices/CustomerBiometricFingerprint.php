<?php

declare(strict_types=1);

namespace App\Models\System\Devices;

use App\Models\System\Customers\{Customer};
use App\Models\System\Organizations\{Company};
use Illuminate\Database\Eloquent\{Model};

class CustomerBiometricFingerprint extends Model {
    protected $table = "customer_biometric_fingerprints";

    protected $primaryKey = "id";

    public $incrementing = true;

    public $timestamps = true;

    public static $snakeAttributes = true;

    protected $fillable = [
        "customer_id",
        "biometric_device_id",
        "device_user_id",
        "finger_index",
        "fingerprint_template",
        "description",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $appends = [
        "formatted_status",
    ];

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    /**
     * Get statuses list
     */
    public static function getStatuses(string $type = "all", ?string $code = ""): array {

        $statuses = [
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ];

        return \App\Helpers\System\Utilities::getValues($statuses, $type, $code);

    }

    // Relationships

    public function customer() {

        return $this->belongsTo(Customer::class, "customer_id", "id");

    }

    public function biometricDevice() {

        return $this->belongsTo(BiometricDevice::class, "biometric_device_id", "id");

    }
}
