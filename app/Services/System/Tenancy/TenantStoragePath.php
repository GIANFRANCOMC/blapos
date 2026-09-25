<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use RuntimeException;

final class TenantStoragePath {
    public static function for(string $relativePath): string {

        $tenant = app(TenantContext::class)->get();

        if(!$tenant) {

            throw new RuntimeException("No existe un contexto tenant activo para almacenar el archivo.");

        }

        $cleanPath = trim(str_replace("..", "", str_replace("\\", "/", $relativePath)), "/");

        return "tenants/{$tenant->public_id}/{$cleanPath}";

    }

    public static function sales(string $relativePath = ""): string {

        return self::for("sales/{$relativePath}");

    }

    public static function purchases(string $relativePath = ""): string {

        return self::for("purchases/{$relativePath}");

    }

    public static function branding(string $relativePath = ""): string {

        return self::for("branding/{$relativePath}");

    }

    public static function backups(string $relativePath = ""): string {

        return self::for("backups/{$relativePath}");

    }
}
