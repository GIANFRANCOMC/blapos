<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Products;

use App\Models\System\Catalogs\{Item};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService, MasterReferenceDataService};
use stdClass;

final class ProductConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "product";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::forUser($userId);

        return self::data([
            "brands" => self::data([
                "records" => $references->brands(),
            ]),
            "categories" => self::data([
                "records" => $references->categories(),
            ]),
            "currencies" => self::data([
                "records" => MasterReferenceDataService::currencies($companyId),
            ]),
            "warehouses" => self::data([
                "records" => $references->stockWarehouses(),
            ]),
            "internal_code_prefixes" => self::internalCodePrefixes($companyId),
            "statuses" => Item::getStatuses(),
        ]);

    }
}
