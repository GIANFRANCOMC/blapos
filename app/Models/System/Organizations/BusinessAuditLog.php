<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use Illuminate\Database\Eloquent\{Model};

final class BusinessAuditLog extends Model {
    protected $table = "business_audit_logs";

    public $timestamps = false;

    protected $fillable = [
        "branch_id",
        "user_id",
        "module",
        "action",
        "auditable_type",
        "auditable_id",
        "summary",
        "before_data",
        "after_data",
        "context",
        "ip_address",
        "user_agent",
        "occurred_at",
    ];

    protected $casts = [
        "before_data" => "array",
        "after_data" => "array",
        "context" => "array",
        "occurred_at" => "datetime",
    ];
}
