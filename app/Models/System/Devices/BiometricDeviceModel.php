<?php

declare(strict_types=1);

namespace App\Models\System\Devices;

use App\Models\System\Organizations\{Company};
use Illuminate\Database\Eloquent\{Model};

final class BiometricDeviceModel extends Model {
    protected $table = "biometric_device_models";

    protected $fillable = [
        "biometric_device_brand_id",
        "slug",
        "name",
        "description",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function brand() {

        return $this->belongsTo(BiometricDeviceBrand::class, "biometric_device_brand_id", "id");

    }

    public function devices() {

        return $this->hasMany(BiometricDevice::class, "biometric_device_model_id", "id");

    }
}
