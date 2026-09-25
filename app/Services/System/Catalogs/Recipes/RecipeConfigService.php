<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Recipes;

use App\Models\System\Catalogs\{Item, RecipeDish};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService, MasterReferenceDataService};
use stdClass;

final class RecipeConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "recipe";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::for($companyId, $userId);

        return self::data([
            "items" => self::data([
                "records" => $references->saleItems(),
            ]),
            "ingredients" => self::data([
                "records" => Item::query()
                    ->where("type", "product")
                    ->where("status", "active")
                    ->with(["currency", "brand"])
                    ->orderBy("name")
                    ->get(),
            ]),
            "currencies" => self::data([
                "records" => MasterReferenceDataService::currencies($companyId),
            ]),
            "internal_code_prefixes" => self::internalCodePrefixes($companyId),
            "statuses" => RecipeDish::getStatuses(),
        ]);

    }
}
