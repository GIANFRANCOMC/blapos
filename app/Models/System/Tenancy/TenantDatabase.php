<?php

declare(strict_types=1);

namespace App\Models\System\Tenancy;

use Illuminate\Database\Eloquent\{Model};
use Illuminate\Support\{Str};

final class TenantDatabase extends Model {
    protected $connection = "landlord";

    protected $table = "tenant_databases";

    protected $fillable = [
        "public_id", "slug",
        "database_name",
        "status",
        "last_resolved_at",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "last_resolved_at" => "datetime",
    ];

    protected static function booted(): void {

        self::creating(function(TenantDatabase $tenant): void {

            $tenant->public_id ??= (string) Str::uuid();

        });

    }

    public function getRouteKeyName(): string {

        return "public_id";

    }

    public function domains() {

        return $this->hasMany(TenantDomain::class, "tenant_database_id");

    }
}
