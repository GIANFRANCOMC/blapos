<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\{Http};

final class TurnstileVerificationService {

    private const VERIFY_URL = "https://challenges.cloudflare.com/turnstile/v0/siteverify";

    public static function enabled(): bool {

        return (bool) config("public_access.captcha.enabled")
            && trim((string) config("public_access.captcha.secret")) !== "";

    }

    public static function verify(?string $token, ?string $ipAddress = null): bool {

        if(!self::enabled()) {

            return true;

        }

        if(trim((string) $token) === "") {

            return false;

        }

        try {

            $response = Http::asForm()
                ->timeout((int) config("public_access.captcha.timeout_seconds", 5))
                ->retry(1, 100)
                ->post(self::VERIFY_URL, [
                    "secret" => (string) config("public_access.captcha.secret"),
                    "response" => $token,
                    "remoteip" => $ipAddress,
                ]);

            return $response->successful() && $response->json("success") === true;

        }catch(\Throwable) {

            return false;

        }

    }

}
