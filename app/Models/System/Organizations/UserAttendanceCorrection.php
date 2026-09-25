<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use Illuminate\Database\Eloquent\{Model};

final class UserAttendanceCorrection extends Model {
    protected $table = "user_attendance_corrections";

    protected $fillable = [
        "user_attendance_id", "requested_by", "reviewed_by",
        "requested_check_in_at", "requested_check_out_at", "reason",
        "review_note", "status", "reviewed_at",
    ];

    protected $casts = [
        "requested_check_in_at" => "datetime",
        "requested_check_out_at" => "datetime",
        "reviewed_at" => "datetime",
    ];
}
