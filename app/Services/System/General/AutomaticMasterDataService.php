<?php

declare(strict_types=1);

namespace App\Services\System\General;

use App\Services\System\Organizations\Companies\{CompanySectionService};
use InvalidArgumentException;

/**
 * Read-only master values derived from the tenant's current configuration.
 */
final class AutomaticMasterDataService {

    public const CUSTOMER_FINGERPRINT_REGISTRATION = "customer_fingerprint_registration";

    private const DEFINITIONS = [
        self::CUSTOMER_FINGERPRINT_REGISTRATION => [
            "name" => "Registro de huella de clientes",
            "description" => "Permite registrar huellas desde Clientes cuando Asistencias de clientes está activo.",
            "source" => "Módulos del tenant",
            "condition_label" => "Asistencias de clientes activo",
            "condition" => "module_enabled",
            "module_route" => "tracking_attendances.index",
        ],
    ];

    public static function enabled(string $key): bool {

        $definition = self::DEFINITIONS[$key]
            ?? throw new InvalidArgumentException("El dato automático solicitado no existe.");

        return match ($definition["condition"]) {
            "module_enabled" => CompanySectionService::isModuleEnabled($definition["module_route"]),
        };

    }

    public static function list(): array {

        return collect(self::DEFINITIONS)
            ->map(function(array $definition, string $key): array {

                return [
                    "code" => $key,
                    "name" => $definition["name"],
                    "description" => $definition["description"],
                    "source" => $definition["source"],
                    "condition_label" => $definition["condition_label"],
                    "condition" => $definition["condition"],
                    "module_route" => $definition["module_route"],
                    "value_type" => "boolean",
                    "value" => self::enabled($key),
                ];

            })
            ->values()
            ->all();

    }

}
