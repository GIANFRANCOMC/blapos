<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantConnectionManager, TenantContext};
use Closure;
use RuntimeException;

final class UseTenantConnection {

    public function __construct(private readonly int $tenantDatabaseId) {
    }

    public function handle(object $job, Closure $next): void {

        $tenant = TenantDatabase::query()
            ->where("status", "active")
            ->find($this->tenantDatabaseId);

        if(!$tenant) {

            throw new RuntimeException("El tenant del trabajo no existe o está inactivo.");

        }

        $manager = app(TenantConnectionManager::class);

        $context = app(TenantContext::class);

        try {

            $manager->connect($tenant);
            $context->set($tenant);
            $next($job);

        }finally {

            $context->set(null);
            $manager->disconnect();

        }

    }

}
