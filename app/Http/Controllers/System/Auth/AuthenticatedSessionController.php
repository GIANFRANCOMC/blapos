<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Auth;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\{Controller};
use App\Http\Requests\System\Auth\{LoginRequest};
use App\Providers\{RouteServiceProvider};
use App\Services\System\Auth\{AuthenticationAuditService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth};
use Illuminate\View\{View};

class AuthenticatedSessionController extends Controller {
    /**
     * Display the login view.
     */
    public function create(Request $request): View {

        $data = Utilities::getDefaultData();

        $data->company = app(TenantCompanyContext::class)->get()->load("socialsMedia");

        return view("System/auth/login", compact("data"));

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
