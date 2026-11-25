<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Auth;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\{Controller};
use App\Http\Requests\System\Auth\{LoginRequest};
use App\Providers\{RouteServiceProvider};
use App\Services\System\Auth\{AuthenticationAuditService};
use App\Services\System\Organizations\Companies\{CompanyBrandingService};
use App\Services\System\Tenancy\{TenantCompanyContext, TenantContext};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, Storage};
use Illuminate\View\{View};
use Symfony\Component\HttpFoundation\{StreamedResponse};

class AuthenticatedSessionController extends Controller {

    /**
     * Display the login view.
     */
    public function create(CompanyBrandingService $branding): View {

        $data = Utilities::getDefaultData();
        $data->company = app(TenantCompanyContext::class)->get()->load("socialsMedia");
        $data->loginImage = $branding->resolve("login_image", $data->company->login_image, "login.image");
        $data->combinationMark = $branding->resolve("combinationmark", $data->company->combinationmark, "login.brand");

        return view("System/auth/login", compact("data"));
    }

    public function image(CompanyBrandingService $branding, TenantContext $tenant, string $resource): StreamedResponse|RedirectResponse {

        abort_unless($tenant->active(), 404);

        $company = app(TenantCompanyContext::class)->get();
        $path = $branding->configuredPath($resource, $company->{$resource});
        $stream = $path === null ? null : Storage::disk("public")->readStream($path);
        $cacheHeaders = ["Cache-Control" => "private, no-store"];
        if(!is_resource($stream)) {
            return redirect()->to($branding->defaultUrl($resource), 302, $cacheHeaders);
        }

        $contentType = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === "png" ? "image/png" : "image/jpeg";

        return response()->stream(static function() use ($stream): void {
            try {
                fpassthru($stream);
            }finally {
                fclose($stream);
            }
        }, 200, $cacheHeaders + ["Content-Type" => $contentType]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse {

        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put("_user_session_version", max(1, (int) (Auth::user()->session_version ?? 1)));
        AuthenticationAuditService::record($request, "login", "success", Auth::user());

        return redirect()->intended(RouteServiceProvider::HOME);

    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse {

        $user = Auth::user();

        AuthenticationAuditService::record($request, "logout", "success", $user);

        Auth::guard("web")->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect("/");

    }

}
