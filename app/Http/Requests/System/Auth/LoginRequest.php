<?php

namespace App\Http\Requests\System\Auth;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{User};
use App\Services\Security\{TurnstileVerificationService};
use App\Services\System\Auth\{AuthenticationAuditService};
use App\Services\System\Tenancy\{TenantCompanyContext, TenantContext};
use Illuminate\Auth\Events\{Lockout};
use Illuminate\Foundation\Http\{FormRequest};
use Illuminate\Http\Exceptions\{HttpResponseException};
use Illuminate\Support\Facades\{Auth, Hash, RateLimiter};
use Illuminate\Support\{Str};
use Illuminate\Validation\{ValidationException};

class LoginRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {

        return true;

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array {

        return [
            "email" => ["required", "string", "email"],
            "password" => ["required", "string"],
        ];

    }

    public function messages() {

        return [

        ];

    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void {

        $this->ensureIsNotRateLimited();

        $credentials = $this->only("email", "password");
        $company = app(TenantCompanyContext::class)->get();

        // Check if the company is active

        if(!Utilities::isDefined($company) || $company->status !== "active") {

            AuthenticationAuditService::record(
                $this,
                "login",
                "blocked",
                null,
                (string) ($credentials["email"] ?? ""),
                "Empresa inexistente o inactiva."
            );

            throw new HttpResponseException(redirect("/"));

        }

        $user = User::where("email", $credentials["email"])
            ->whereIn("status", ["active"])
            ->first();

        // Attempt to authenticate the user

        if(!Utilities::isDefined($user) || !Hash::check($credentials["password"], $user->password)) {

            RateLimiter::hit($this->throttleKey());
            AuthenticationAuditService::record(
                $this,
                "login",
                "failure",
                $user,
                (string) ($credentials["email"] ?? ""),
                "Credenciales inválidas."
            );

            throw ValidationException::withMessages([
                "email" => trans("auth.failed"),
            ]);

        }

        if(!TurnstileVerificationService::verify(
            $this->input("cf-turnstile-response"),
            $this->ip()
        )) {

            AuthenticationAuditService::record(
                $this,
                "login",
                "blocked",
                $user,
                (string) $credentials["email"],
                "Desafío antiabuso rechazado."
            );

            throw ValidationException::withMessages([
                "captcha" => trans("auth.captcha"),
            ]);

        }

        Auth::login($user, $this->boolean("remember"));

        RateLimiter::clear($this->throttleKey());

    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void {

        if(!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {

            return;

        }

        event(new Lockout($this));

        AuthenticationAuditService::record(
            $this,
            "lockout",
            "blocked",
            null,
            (string) $this->input("email"),
            "Límite de intentos de inicio de sesión alcanzado."
        );

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            "email" => trans("auth.throttle", [
                "seconds" => $seconds,
                "minutes" => ceil($seconds / 60),
            ]),
        ]);

    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string {

        $tenantId = app(TenantContext::class)->get()?->id ?? 0;

        return Str::transliterate(
            $tenantId."|".Str::lower((string) $this->input("email"))."|".$this->ip()
        );

    }
}
