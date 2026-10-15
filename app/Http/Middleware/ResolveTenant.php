<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\System\Tenancy\{TenantAdministrationService, TenantConnectionManager, TenantContext, TenantResolver};
use Closure;
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{Config};
use Illuminate\Support\{Str};
use Symfony\Component\HttpFoundation\{Response};

final class ResolveTenant {

    public function __construct(
        private readonly TenantResolver $resolver,
        private readonly TenantConnectionManager $connectionManager,
        private readonly TenantContext $context,
        private readonly TenantAdministrationService $administration
    ) {
    }

    public function handle(Request $request, Closure $next): Response {

        if($this->isPlatformHost($request->getHost())) {

            $this->context->set(null);
            $this->connectionManager->disconnect();
            Config::set("session.cookie", (string) config("tenancy.platform_session_cookie", "blapos_platform_session"));
            Config::set("session.domain", null);

            return $next($request);

        }

        $tenant = $this->resolver->resolveByHost($request->getHost());

        if(!$tenant) {

            $this->context->set(null);
            $host = $this->resolver->normalizeHost($request->getHost());
            $deduplicationKey = "tenancy:unknown-host:".hash("sha256", $host."|".$request->ip());

            if(cache()->add($deduplicationKey, true, now()->addMinutes(5))) {

                $this->administration->audit(
                    null,
                    "unknown_host_rejected",
                    "blocked",
                    ["method" => $request->method(), "path" => $request->path()],
                    null,
                    $host,
                    $request->ip()
                );

            }
            abort(404);

        }

        $this->connectionManager->connect($tenant);

        $this->context->set($tenant);
        Config::set("session.cookie", $this->tenantSessionCookieName($tenant->slug));
        Config::set("session.domain", null);

        return $next($request);

    }

    private function isPlatformHost(string $host): bool {

        $host = $this->resolver->normalizeHost($host);
        $subdomain = (string) config("tenancy.platform_subdomain", "app");
        $baseDomain = (string) config("tenancy.base_domain");

        return $host === "{$subdomain}.{$baseDomain}";

    }

    private function tenantSessionCookieName(string $slug): string {

        $prefix = (string) config("tenancy.session_cookie_prefix", "blapos_tenant");
        $hash = substr(hash("sha256", $slug), 0, 12);

        return Str::slug("{$prefix}_{$slug}_{$hash}", "_")."_session";

    }

}
