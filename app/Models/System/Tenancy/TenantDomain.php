<?php

declare(strict_types=1);

namespace App\Models\System\Tenancy;

use Illuminate\Database\Eloquent\{Model};

final class TenantDomain extends Model {

    protected $connection = "landlord";

    protected $table = "tenant_domains";

    protected $fillable = [
        "tenant_database_id",
        "domain",
        "type",
        "is_primary",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "is_primary" => "boolean",
    ];

    public function tenantDatabase() {

        return $this->belongsTo(TenantDatabase::class, "tenant_database_id");

    }

}
