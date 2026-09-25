<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use App\Helpers\System\{Utilities};
use Illuminate\Database\Eloquent\{Model};

final class UserAttendance extends Model {
    protected $table = "user_attendances";

    protected $fillable = [
        "branch_id",
        "user_id",
        "work_date",
        "checked_in_at",
        "checked_out_at",
        "worked_minutes",
        "ordinary_minutes",
        "late_minutes",
        "overtime_minutes",
        "break_minutes",
        "source_type",
        "source_reference",
        "observation",
        "motive",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "work_date" => "date",
        "checked_in_at" => "datetime",
        "checked_out_at" => "datetime",
        "worked_minutes" => "integer",
        "ordinary_minutes" => "integer",
        "late_minutes" => "integer",
        "overtime_minutes" => "integer",
        "break_minutes" => "integer",
    ];

    protected $appends = [
        "worked_hours",
    ];

    public function getWorkedHoursAttribute(): float {

        return Utilities::round(
            ((int) ($this->attributes["worked_minutes"] ?? 0)) / 60,
            null,
            null
        );

    }

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }

    public function breaks() {

        return $this->hasMany(UserAttendanceBreak::class, "user_attendance_id", "id");

    }

    public function corrections() {

        return $this->hasMany(UserAttendanceCorrection::class, "user_attendance_id", "id");

    }
}
