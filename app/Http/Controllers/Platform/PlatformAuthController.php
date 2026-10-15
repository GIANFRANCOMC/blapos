<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\{Controller};
use App\Models\System\Tenancy\{PlatformUser};
use App\Services\System\Tenancy\{TenantAdministrationService};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Hash};
use Illuminate\View\{View};

final class PlatformAuthController extends Controller {

    public function create(Request $request): View|RedirectResponse {

        if($request->session()->has("platform_user_id")) {

            return redirect()->route("platform.tenants.index");

        }

        return view("Platform.auth.login");

    }

    public function store(Request $request, TenantAdministrationService $administration): RedirectResponse {

        $credentials = $request->validate([
            "email" => ["required", "email", "max:190"],
            "password" => ["required", "string", "max:255"],
        ]);

        $user = PlatformUser::query()
            ->where("email", strtolower($credentials["email"]))
            ->where("status", "active")
            ->first();

        if(!$user || !Hash::check($credentials["password"], $user->password)) {

            $administration->audit(null, "platform_login_failed", "blocked", ["email" => strtolower($credentials["email"])], strtolower($credentials["email"]), $request->getHost(), $request->ip());

            return back()->withErrors(["email" => "Las credenciales no son válidas."])->onlyInput("email");

        }

        $request->session()->regenerate();

        $request->session()->put("platform_user_id", $user->id);
        $request->session()->put("platform_session_version", (int) $user->session_version);
        $user->forceFill(["last_login_at" => now(), "last_login_ip" => $request->ip()])->save();
        $administration->audit(null, "platform_login", "success", [], $user->email, $request->getHost(), $request->ip());

        return redirect()->intended(route("platform.tenants.index"));

    }

    public function destroy(Request $request): RedirectResponse {

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route("platform.login");

    }

}
