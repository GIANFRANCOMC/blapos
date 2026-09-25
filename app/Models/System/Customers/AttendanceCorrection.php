<?php

declare(strict_types=1);

namespace App\Models\System\Customers;

use App\Models\System\Organizations\{Company, User};
use Illuminate\Database\Eloquent\{Model};

final class AttendanceCorrection extends Model {
    protected $table = "attendance_corrections";

    protected $fillable = [
        "attendance_id",
        "requested_by",
        "previous_start_date",
        "previous_end_date",
        "requested_start_date",
        "requested_end_date",
        "reason",
        "status",
        "reviewed_by",
        "review_note",
        "reviewed_at",
    ];

    protected $casts = [
        "previous_start_date" => "datetime",
        "previous_end_date" => "datetime",
        "requested_start_date" => "datetime",
        "requested_end_date" => "datetime",
        "reviewed_at" => "datetime",
    ];


    public function attendance() {

        return $this->belongsTo(Attendance::class);

    }

    public function requestedBy() {

        return $this->belongsTo(User::class, "requested_by");

    }

    public function reviewedBy() {

        return $this->belongsTo(User::class, "reviewed_by");

    }
}
