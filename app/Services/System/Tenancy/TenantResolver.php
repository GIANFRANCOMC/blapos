<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Enums\System\Tenancy\{TenantStatus};
use App\Models\System\Tenancy\{TenantDatabase, TenantDomain};
use Illuminate\Support\Facades\{Cache};
use Illuminate\Support\{Str};

final class TenantResolver {
    public static function cacheKey(string $host): string {

        return "tenancy:resolver:".hash("sha256", strtolower(trim($host)));

    }

    public function resolveByHost(string $host): ?TenantDatabase {

        $host = $this->normalizeHost($host);
        $subdomain = $this->extractSubdomain($host);

        if($subdomain === null) {

            return null;

        }

        return $this->resolveRegisteredSubdomain($host, $subdomain);

    }

    public function normalizeHost(string $host): string {

        $host = strtolower(trim($host));

        return Str::before($host, ":");

    }

    private function extractSubdomain(string $host): ?string {

        $baseDomain = strtolower(trim((string) config("tenancy.base_domain")));
        $suffix = ".{$baseDomain}";

        if($host === "" || $baseDomain === "" || !Str::endsWith($host, $suffix)) {

            return null;

        }

        $subdomain = Str::beforeLast($host, $suffix);

        if(!preg_match("/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\$/", $subdomain)) {

            return null;

        }

        if(in_array($subdomain, config("tenancy.reserved_subdomains", []), true)) {

            return null;

        }

        return $subdomain;

    }

    private function resolveRegisteredSubdomain(string $host, string $subdomain): ?TenantDatabase {

        $loadTenant = static function() use ($host, $subdomain): ?TenantDatabase {

            $domain = TenantDomain::query()
                ->where("domain", $host)
                ->where("type", "subdomain")
                ->where("status", "active")
                ->with("tenantDatabase")
                ->first();

            if(!$domain || !$domain->tenantDatabase) {

                return null;

            }

            $tenant = $domain->tenantDatabase;

            if($tenant->status !== TenantStatus::ACTIVE->value || $tenant->slug !== $subdomain) {

                return null;

            }

            if(!$tenant->last_resolved_at || $tenant->last_resolved_at->lt(now()->subMinutes(15))) {

                $tenant->forceFill(["last_resolved_at" => now()])->save();

            }

            return $tenant;

        };

        $cacheSeconds = (int) config("tenancy.resolver_cache_seconds", 60);

        if($cacheSeconds <= 0) {

            return $loadTenant();

        }

        return Cache::remember(
            self::cacheKey($host),
            now()->addSeconds($cacheSeconds),
            $loadTenant
        );

    }
}
