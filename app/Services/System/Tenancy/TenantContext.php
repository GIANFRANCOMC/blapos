<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Models\System\Tenancy\{TenantDatabase};
use Illuminate\Support\Facades\{DB};

final class TenantContext {
    private ?TenantDatabase $tenant = null;

    public function set(?TenantDatabase $tenant): void {

        $this->tenant = $tenant;

    }

    public function get(): ?TenantDatabase {

        return $this->tenant;

    }

    public function active(): bool {

        return $this->tenant !== null;

    }

    public function cacheNamespace(): string {

        if($this->tenant instanceof TenantDatabase) {

            return "tenant:".$this->tenant->public_id;

        }

        $connection = DB::getDefaultConnection();

        $database = (string) DB::connection($connection)->getDatabaseName();

        return "tenant:".hash("sha256", $connection.":".$database);

    }
}
