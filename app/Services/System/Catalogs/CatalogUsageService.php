<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs;

use App\Services\System\Tenancy\TenantCompanyContext;
use Illuminate\Support\Facades\DB;

final class CatalogUsageService {

    private const ROUTES = [
        "product" => "products.index",
        "service" => "services.index",
        "subscription" => "subscriptions.index",
    ];

    /** @return array<int, string> Item types enabled for the current company. */
    public static function enabledTypes(): array {

        $routes = DB::table("companies_sub_sections as company_modules")
            ->join("sub_sections as modules", "modules.id", "=", "company_modules.sub_section_id")
            ->join("sections", "sections.id", "=", "modules.section_id")
            ->where("company_modules.company_id", app(TenantCompanyContext::class)->id())
            ->where("company_modules.status", "active")
            ->where("modules.status", "active")
            ->where("sections.status", "active")
            ->whereIn("modules.dom_route", array_values(self::ROUTES))
            ->pluck("modules.dom_route")
            ->all();

        return array_keys(array_filter(
            self::ROUTES,
            fn(string $route) => in_array($route, $routes, true)
        ));

    }

}
