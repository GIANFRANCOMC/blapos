<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use Illuminate\Support\Facades\{Schema};
use RuntimeException;

final class LandlordSchemaService {

    private static bool $resolved = false;

    public static function ensure(): void {

        if(self::$resolved) {

            return;

        }

        $schema = Schema::connection((string) config("tenancy.landlord_connection", "landlord"));

        $requiredTables = [
            "platform_users",
            "tenant_databases",
            "tenant_domains",
            "tenant_audit_logs",
            "tenant_announcements",
        ];

        $missingTables = collect($requiredTables)
            ->reject(fn(string $table) => $schema->hasTable($table))
            ->values();

        if($missingTables->isNotEmpty()) {

            throw new RuntimeException(
                "El esquema landlord está incompleto (".$missingTables->implode(", ")."). Ejecuta php artisan platform:install."
            );

        }

        self::$resolved = true;

    }

}
