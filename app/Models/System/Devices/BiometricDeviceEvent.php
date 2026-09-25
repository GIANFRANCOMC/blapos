<?php

declare(strict_types=1);

namespace App\Models\System\Devices;

use Illuminate\Database\Eloquent\{Model};

final class BiometricDeviceEvent extends Model {
    protected $table = "biometric_device_events";

    protected $fillable = [
        "biometric_device_id",
        "event_uuid",
        "event_type",
        "subject_type",
        "device_user_id",
        "occurred_at",
        "payload",
        "processing_status",
        "attempts",
        "last_error",
        "processed_at",
    ];

    protected $casts = [
        "occurred_at" => "datetime",
        "processed_at" => "datetime",
        "payload" => "array",
    ];
}
