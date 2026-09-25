<?php

declare(strict_types=1);

namespace App\Models\System\Operations;

use App\Models\System\Organizations\{User};
use Illuminate\Database\Eloquent\{Model};

final class ServiceSessionEvent extends Model {
    protected $table = "service_session_events";

    public $timestamps = false;

    protected $fillable = [
        "service_session_id",
        "service_session_item_id",
        "user_id",
        "event_type",
        "previous_status",
        "new_status",
        "note",
        "metadata",
        "occurred_at",
    ];

    protected $casts = [
        "metadata" => "array",
        "occurred_at" => "datetime",
    ];

    public function session() {

        return $this->belongsTo(ServiceSession::class, "service_session_id", "id");

    }

    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }
}
