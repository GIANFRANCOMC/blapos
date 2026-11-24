<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs;

use App\Services\System\Organizations\Companies\{CompanySectionService};

final class CatalogUsageService {

    private const ROUTES = [
        "product" => "products.index",
        "service" => "services.index",
        "subscription" => "subscriptions.index",
    ];

    /** @return array<int, string> Item types enabled for the current company. */
    public static function enabledTypes(): array {

        $routes = CompanySectionService::enabledRoutes(array_values(self::ROUTES));

        return array_keys(array_filter(
            self::ROUTES,
            fn(string $route) => in_array($route, $routes, true)
        ));

    }

}
