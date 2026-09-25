<?php

declare(strict_types=1);

namespace App\Services\System\Auth;

use App\Models\System\Organizations\{AuthenticationEvent, User};
use App\Services\System\Tenancy\{TenantContext};
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{Schema};
use Throwable;

final class AuthenticationAuditService {
    public static function record(
        Request $request,
        string $eventType,
        string $result,
        ?User $user = null,
        ?string $email = null,
        ?string $reason = null
    ): void {

        try {

            if(!Schema::hasTable("authentication_events")) {

                return;

            }

            $tenant = app(TenantContext::class)->get();

            $sessionId = $request->hasSession() ? $request->session()->getId() : null;

            AuthenticationEvent::query()->create([
                "user_id" => $user?->id,
                "tenant_slug" => $tenant?->slug,
                "event_type" => $eventType,
                "result" => $result,
                "email" => $email ? mb_strtolower(trim($email)) : $user?->email,
                "ip_address" => $request->ip(),
                "user_agent" => mb_substr((string) $request->userAgent(), 0, 500),
                "session_hash" => $sessionId ? hash("sha256", $sessionId) : null,
                "reason" => $reason ? mb_substr($reason, 0, 500) : null,
                "occurred_at" => now(),
            ]);

        }catch(Throwable) {
            // La auditoría no debe impedir el acceso ni revelar fallos internos al visitante.
        }

    }
}
