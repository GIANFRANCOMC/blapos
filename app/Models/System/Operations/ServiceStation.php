<?php

declare(strict_types=1);

namespace App\Models\System\Operations;

use App\Models\System\Organizations\{Branch, Company};
use Illuminate\Database\Eloquent\{Model};

final class ServiceStation extends Model {
    protected $table = "service_stations";

    protected $fillable = [
        "branch_id",
        "service_floor_id",
        "code",
        "name",
        "station_type",
        "capacity",
        "position_x",
        "position_y",
        "color",
        "shape",
        "description",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "capacity" => "integer",
        "position_x" => "App\\Casts\\System\\ConfigurableDecimal",
        "position_y" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function floor() {

        return $this->belongsTo(ServiceFloor::class, "service_floor_id", "id");

    }

    public function sessions() {

        return $this->hasMany(ServiceSession::class, "service_station_id", "id");

    }

    public function activeSession() {

        return $this->hasOne(ServiceSession::class, "service_station_id", "id")
            ->whereIn("status", ["pending", "in_progress"])
            ->latestOfMany();

    }
}
