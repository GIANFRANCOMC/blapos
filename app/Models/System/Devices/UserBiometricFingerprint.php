<?php

declare(strict_types=1);

namespace App\Models\System\Devices;

use App\Models\System\Organizations\{Company, User};
use Illuminate\Database\Eloquent\{Model};

final class UserBiometricFingerprint extends Model {
    protected $table = "user_biometric_fingerprints";

    protected $fillable = [
        "user_id",
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

    protected $hidden = [
        "fingerprint_template",
    ];

    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }

    public function device() {

        return $this->belongsTo(BiometricDevice::class, "biometric_device_id", "id");

    }
}
