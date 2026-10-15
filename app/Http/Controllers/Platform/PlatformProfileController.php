<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\{Controller};
use App\Services\System\Tenancy\{TenantAdministrationService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Hash};
use Illuminate\Validation\Rules\{Password};
use Illuminate\Validation\{Rule, ValidationException};

final class PlatformProfileController extends Controller {

    public function update(Request $request, TenantAdministrationService $administration): JsonResponse {

        $user = $request->attributes->get("platformUser");
        $data = $request->validate([
            "name" => ["required", "string", "max:150"],
            "email" => [
                "required", "email", "max:190",
                Rule::unique("landlord.platform_users", "email")->ignore($user->id),
            ],
            "current_password" => ["required", "string", "max:255"],
            "password" => [
                "nullable", "string", "max:255", "confirmed",
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if(!Hash::check($data["current_password"], $user->password)) {

            throw ValidationException::withMessages([
                "current_password" => "La contraseña actual no es correcta.",
            ]);

        }

        $attributes = [
            "name" => trim($data["name"]),
            "email" => strtolower($data["email"]),
            "updated_at" => now(),
        ];

        if(!empty($data["password"])) {

            $attributes["password"] = Hash::make($data["password"]);
            $attributes["session_version"] = (int) $user->session_version + 1;

        }

        $user->forceFill($attributes)->save();

        $request->session()->put("platform_session_version", (int) $user->session_version);

        $administration->audit(
            null,
            "platform_profile_updated",
            "success",
            ["password_changed" => !empty($data["password"])],
            $user->email,
            $request->getHost(),
            $request->ip()
        );

        return response()->json([
            "message" => "Perfil actualizado correctamente.",
            "data" => ["name" => $user->name, "email" => $user->email],
        ]);

    }

}
