<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use Illuminate\Database\Eloquent\{Model};

final class UserAttendanceBreak extends Model {
    protected $table = "user_attendance_breaks";

    protected $fillable = [
        "user_attendance_id", "started_at", "ended_at",
        "duration_minutes", "reason", "status", "created_by", "updated_by",
    ];

    protected $casts = [
        "started_at" => "datetime",
        "ended_at" => "datetime",
        "duration_minutes" => "integer",
    ];
}
