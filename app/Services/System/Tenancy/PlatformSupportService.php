<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use Illuminate\Support\Facades\{DB};
use Throwable;

final class PlatformSupportService {

    public const DEFAULT_EMAIL = "gianfranco_29_01@hotmail.com";

    public const DEFAULT_PHONE = "+51 987 057 624";

    /** @return array{email: string, phone: string, phone_href: string} */
    public function contact(): array {

        $email = self::DEFAULT_EMAIL;
        $phone = self::DEFAULT_PHONE;

        try {

            $connection = (string) config("tenancy.landlord_connection", "landlord");
            $database = DB::connection($connection);

            if($database->getSchemaBuilder()->hasTable("platform_settings")) {

                $settings = $database->table("platform_settings")
                    ->whereIn("key", ["support_email", "support_phone"])
                    ->pluck("value", "key");

                $configuredEmail = trim((string) $settings->get("support_email"));
                $configuredPhone = trim((string) $settings->get("support_phone"));

                if(filter_var($configuredEmail, FILTER_VALIDATE_EMAIL)) {

                    $email = $configuredEmail;

                }

                if(preg_match('/^\+?[0-9 ]{7,25}$/', $configuredPhone)) {

                    $phone = $configuredPhone;

                }

            }

        } catch(Throwable) {

            // Las páginas de error y el acceso deben seguir disponibles si landlord falla.

        }

        return [
            "email" => $email,
            "phone" => $phone,
            "phone_href" => preg_replace('/\s+/', '', $phone),
        ];

    }

}
