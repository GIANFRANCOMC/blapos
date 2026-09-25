<?php

declare(strict_types=1);

namespace App\Models\System\Tenancy;

use Illuminate\Database\Eloquent\{Model};

final class TenantAuditLog extends Model {
    protected $connection = "landlord";

    protected $table = "tenant_audit_logs";

    public $timestamps = false;

    protected $fillable = [
        "tenant_database_id",
        "action",
        "result",
        "host",
        "ip_address",
        "actor",
        "context",
        "occurred_at",
    ];

    protected $casts = [
        "context" => "array",
        "occurred_at" => "datetime",
    ];

    public function tenantDatabase() {

        return $this->belongsTo(TenantDatabase::class, "tenant_database_id");

    }
}
