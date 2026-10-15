<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\{Request};
use Symfony\Component\HttpFoundation\{Response};

final class SecurityHeaders {

    public function handle(Request $request, Closure $next): Response {

        $response = $next($request);

        if(function_exists("header_remove")) {

            header_remove("X-Powered-By");

        }

        $response->headers->set("X-Content-Type-Options", "nosniff");

        $response->headers->set("X-Frame-Options", "SAMEORIGIN");
        $response->headers->set("Referrer-Policy", "strict-origin-when-cross-origin");
        $response->headers->set("Permissions-Policy", "camera=(self), microphone=(), geolocation=(), payment=()");
        $response->headers->set(
            "Content-Security-Policy",
            "base-uri 'self'; frame-ancestors 'self'; object-src 'none'"
        );

        if($request->isSecure() && app()->environment("production")) {

            $response->headers->set("Strict-Transport-Security", "max-age=31536000; includeSubDomains");

        }

        if(
            $request->hasSession()
            && auth()->check()
            && str_contains((string) $response->headers->get("Content-Type"), "text/html")
        ) {

            $response->headers->set("Cache-Control", "no-store, private");
            $response->headers->set("Pragma", "no-cache");

        }

        return $response;

    }

}
