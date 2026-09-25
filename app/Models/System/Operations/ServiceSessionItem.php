<?php

declare(strict_types=1);

namespace App\Models\System\Operations;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Model};

final class ServiceSessionItem extends Model {
    protected $table = "service_session_items";

    protected $fillable = [
        "service_session_id",
        "item_id",
        "assigned_user_id",
        "name",
        "item_type",
        "quantity",
        "unit_price",
        "status",
        "started_at",
        "ended_at",
        "duration_minutes",
        "paused_minutes",
        "preparation_status",
        "preparation_started_at",
        "ready_at",
        "delivered_at",
        "observation",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "unit_price" => "App\\Casts\\System\\ConfigurableDecimal",
        "started_at" => "datetime",
        "ended_at" => "datetime",
        "duration_minutes" => "integer",
        "paused_minutes" => "integer",
        "preparation_started_at" => "datetime",
        "ready_at" => "datetime",
        "delivered_at" => "datetime",
    ];

    public function session() {

        return $this->belongsTo(ServiceSession::class, "service_session_id", "id");

    }

    public function item() {

        return $this->belongsTo(Item::class, "item_id", "id");

    }

    public function assignedUser() {

        return $this->belongsTo(User::class, "assigned_user_id", "id");

    }
}
