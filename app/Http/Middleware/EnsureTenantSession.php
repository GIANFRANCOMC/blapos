<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\System\Tenancy\{TenantContext};
use Closure;
use Illuminate\Http\{Request};
use Symfony\Component\HttpFoundation\{Response};

final class EnsureTenantSession {

    private const SESSION_TENANT_KEY = "_tenant_database_id";

    public function __construct(
        private readonly TenantContext $context
    ) {
    }

    public function handle(Request $request, Closure $next): Response {

        if(!$request->hasSession()) {

            return $next($request);

        }

        $tenant = $this->context->get();

        $session = $request->session();
        $currentTenantId = $tenant?->id;
        $sessionTenantId = $session->get(self::SESSION_TENANT_KEY);

        if($currentTenantId && $sessionTenantId && (int) $sessionTenantId !== (int) $currentTenantId) {

            $session->invalidate();
            $session->regenerateToken();

        }

        if($currentTenantId) {

            $session->put(self::SESSION_TENANT_KEY, $currentTenantId);

            return $next($request);

        }

        if($sessionTenantId) {

            $session->invalidate();
            $session->regenerateToken();

        }

        return $next($request);

    }

}
