<?php

declare(strict_types=1);

namespace App\Models\System\Operations;

use App\Models\System\Customers\{Customer};
use App\Models\System\Organizations\{Branch, User};
use App\Models\System\Sales\{SaleHeader};
use Illuminate\Database\Eloquent\{Model};

final class ServiceSession extends Model {
    protected $table = "service_sessions";

    protected $fillable = [
        "branch_id",
        "service_station_id",
        "customer_id",
        "assigned_user_id",
        "sale_header_id",
        "opened_by",
        "closed_by",
        "reference",
        "session_type",
        "status",
        "started_at",
        "ended_at",
        "duration_minutes",
        "scheduled_at",
        "expected_end_at",
        "tolerance_minutes",
        "queue_code",
        "observation",
        "cancellation_reason",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "started_at" => "datetime",
        "ended_at" => "datetime",
        "duration_minutes" => "integer", "scheduled_at" => "datetime", "expected_end_at" => "datetime", "tolerance_minutes" => "integer",
    ];

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function station() {

        return $this->belongsTo(ServiceStation::class, "service_station_id", "id");

    }

    public function customer() {

        return $this->belongsTo(Customer::class, "customer_id", "id");

    }

    public function assignedUser() {

        return $this->belongsTo(User::class, "assigned_user_id", "id");

    }

    public function opener() {

        return $this->belongsTo(User::class, "opened_by", "id");

    }

    public function closer() {

        return $this->belongsTo(User::class, "closed_by", "id");

    }

    public function sale() {

        return $this->belongsTo(SaleHeader::class, "sale_header_id", "id");

    }

    public function items() {

        return $this->hasMany(ServiceSessionItem::class, "service_session_id", "id");

    }

    public function events() {

        return $this->hasMany(ServiceSessionEvent::class, "service_session_id", "id")
            ->orderByDesc("occurred_at");

    }
}
