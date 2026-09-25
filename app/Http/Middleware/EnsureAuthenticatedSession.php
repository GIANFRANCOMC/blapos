<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\System\Auth\{AuthenticationAuditService};
use Closure;
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{Auth};
use Symfony\Component\HttpFoundation\{Response};

final class EnsureAuthenticatedSession {
    private const SESSION_VERSION_KEY = "_user_session_version";

    public function handle(Request $request, Closure $next): Response {

        if(!Auth::check() || !$request->hasSession()) {

            return $next($request);

        }

        $user = Auth::user();

        $session = $request->session();
        $currentVersion = max(1, (int) ($user->session_version ?? 1));
        $sessionVersion = $session->get(self::SESSION_VERSION_KEY);

        if($sessionVersion === null) {

            $session->put(self::SESSION_VERSION_KEY, $currentVersion);

            return $next($request);

        }

        if($user->status === "active" && (int) $sessionVersion === $currentVersion) {

            return $next($request);

        }

        AuthenticationAuditService::record(
            $request,
            "session_revoked",
            "blocked",
            $user,
            null,
            $user->status !== "active" ? "Usuario inactivo." : "Versión de sesión obsoleta."
        );

        Auth::guard("web")->logout();
        $session->invalidate();
        $session->regenerateToken();

        if($request->expectsJson()) {

            return response()->json([
                "message" => "La sesión dejó de ser válida. Inicia sesión nuevamente.",
            ], 401);

        }

        return redirect()->route("login")->withErrors([
            "email" => "La sesión dejó de ser válida. Inicia sesión nuevamente.",
        ]);

    }
}
