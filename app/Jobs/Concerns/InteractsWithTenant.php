<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\{UseTenantConnection};
use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantContext};
use RuntimeException;

trait InteractsWithTenant {
    public int $tenantDatabaseId;

    public function initializeTenant(?TenantDatabase $tenant = null): void {

        $tenant ??= app(TenantContext::class)->get();

        if(!$tenant) {

            throw new RuntimeException("El job requiere un tenant activo.");

        }

        $this->tenantDatabaseId = (int) $tenant->getKey();

    }

    public function tenantDatabaseId(): int {

        return $this->tenantDatabaseId;

    }

    public function middleware(): array {

        return [new UseTenantConnection($this->tenantDatabaseId())];

    }
}
