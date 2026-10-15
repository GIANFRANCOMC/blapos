<?php

declare(strict_types=1);

namespace App\Models\System\Tenancy;

use Illuminate\Database\Eloquent\{Model};

final class TenantAnnouncement extends Model {

    protected $connection = "landlord";

    protected $table = "tenant_announcements";

    protected $fillable = [
        "tenant_database_id", "title", "message", "severity", "starts_at", "ends_at",
        "dismissible", "status", "created_by", "updated_by",
    ];

    protected $casts = [
        "starts_at" => "datetime",
        "ends_at" => "datetime",
        "dismissible" => "boolean",
    ];

    public function tenantDatabase() {

        return $this->belongsTo(TenantDatabase::class, "tenant_database_id");

    }

}
