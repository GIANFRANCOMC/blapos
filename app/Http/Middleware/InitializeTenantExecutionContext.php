<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\System\Organizations\{User};
use App\Services\System\Tenancy\{BranchContext, TenantContext};
use Closure;
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{Log};
use Illuminate\Support\{Str};
use Symfony\Component\HttpFoundation\{Response};

final class InitializeTenantExecutionContext {
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly BranchContext $branchContext
    ) {
    }

    public function handle(Request $request, Closure $next): Response {

        $requestId = trim((string) $request->headers->get("X-Request-ID"));
        $requestId = $requestId !== "" && strlen($requestId) <= 100
            ? $requestId
            : (string) Str::uuid();

        $user = $request->user();
        $this->branchContext->setUser($user instanceof User ? $user : null);

        $tenant = $this->tenantContext->get();

        Log::shareContext([
            "request_id" => $requestId,
            "tenant_id" => $tenant?->public_id,
            "tenant_domain" => $request->getHost(),
            "database_name" => $tenant?->database_name,
            "user_id" => $user?->getAuthIdentifier(),
        ]);

        try {

            $response = $next($request);
            $response->headers->set("X-Request-ID", $requestId);

            return $response;

        }finally {

            $this->branchContext->forget();
            Log::flushSharedContext();

        }

    }
}
