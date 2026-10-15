<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\{Limit};
use Illuminate\Foundation\Support\Providers\{RouteServiceProvider as ServiceProvider};
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{RateLimiter, Route};

class RouteServiceProvider extends ServiceProvider {

    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = "/workspace";

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void {

        RateLimiter::for("api", function(Request $request) {

            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());

        });

        RateLimiter::for("platform-login", function(Request $request) {

            $email = strtolower((string) $request->input("email", "unknown"));

            return Limit::perMinute(5)->by(hash("sha256", $email."|".$request->ip()));

        });

        RateLimiter::for("platform-provision", fn(Request $request) => [
            Limit::perMinute(1)->by("platform-provision-minute:".$request->ip()),
            Limit::perHour(6)->by("platform-provision-hour:".$request->ip()),
        ]);

        RateLimiter::for("platform-write", fn(Request $request) => Limit::perMinute(30)
            ->by("platform-write:".((int) $request->session()->get("platform_user_id", 0)).":".$request->ip())
        );

        RateLimiter::for("guest-complaints", function(Request $request) {

            $company = $request->route("company_slug", "unknown");

            return [
                Limit::perMinute((int) config("public_access.complaints.per_minute"))->by("complaint-minute:{$company}:{$request->ip()}"),
                Limit::perDay((int) config("public_access.complaints.per_day"))->by("complaint-day:{$company}:{$request->ip()}"),
            ];

        });

        RateLimiter::for("guest-status", fn(Request $request) => Limit::perMinute((int) config("public_access.status.per_minute"))
            ->by("public-status:{$request->route("company_slug")}:{$request->ip()}")
        );

        RateLimiter::for("guest-attendance", function(Request $request) {

            $company = $request->route("company_slug", "unknown");

            return [
                Limit::perMinute((int) config("public_access.attendance.per_minute"))->by("attendance-minute:{$company}:{$request->ip()}"),
                Limit::perHour((int) config("public_access.attendance.per_hour"))->by("attendance-hour:{$company}:{$request->ip()}"),
            ];

        });

        RateLimiter::for("biometric-events", fn(Request $request) => Limit::perMinute((int) config("public_access.biometric_events.per_minute"))->by(
            "biometric:{$request->route("company_slug")}:".($request->header("X-Device-Key") ?: $request->ip())
        )
        );

        $this->routes(function() {

            Route::middleware("api")
                ->prefix("api")
                ->group(base_path("routes/api.php"));

            Route::domain(config("tenancy.platform_subdomain", "app").".".config("tenancy.base_domain"))
                ->middleware("web")
                ->group(base_path("routes/platform.php"));

            Route::middleware("web")
                ->group(base_path("routes/web.php"));

        });

    }

}
