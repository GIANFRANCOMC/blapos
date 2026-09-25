<?php

declare(strict_types=1);

namespace App\Models\System\Devices;

use App\Models\System\Organizations\{Branch, Company};
use Illuminate\Database\Eloquent\{Model};

class BiometricDevice extends Model {
    protected $table = "biometric_devices";

    protected $fillable = [
        "branch_id", "biometric_device_model_id", "name",
        "serial_number", "ip_address", "port", "device_id", "access_key",
        "secret_encrypted", "credentials_rotated_at", "last_seen_at",
        "description", "status", "created_at", "created_by", "updated_at", "updated_by",
    ];

    protected $appends = ["formatted_status", "brand_name", "model_name"];

    protected $hidden = ["secret_encrypted"];

    protected $casts = [
        "credentials_rotated_at" => "datetime",
        "last_seen_at" => "datetime",
    ];

    public function getFormattedStatusAttribute(): string {

        return self::getStatuses("first", $this->attributes["status"] ?? "")["label"] ?? "";

    }

    public function getBrandNameAttribute(): string {

        return $this->model?->brand?->name ?? "";

    }

    public function getModelNameAttribute(): string {

        return $this->model?->name ?? "";

    }

    public static function getStatuses(string $type = "all", ?string $code = ""): array {

        return \App\Helpers\System\Utilities::getValues([
            ["code" => "active", "label" => "Activo"],
            ["code" => "inactive", "label" => "Inactivo"],
        ], $type, $code);

    }

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function model() {

        return $this->belongsTo(BiometricDeviceModel::class, "biometric_device_model_id", "id");

    }

    public function customerFingerprints() {

        return $this->hasMany(CustomerBiometricFingerprint::class, "biometric_device_id", "id");

    }

    public function events() {

        return $this->hasMany(BiometricDeviceEvent::class, "biometric_device_id", "id");

    }
}
