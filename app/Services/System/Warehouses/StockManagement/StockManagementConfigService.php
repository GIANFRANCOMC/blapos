<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\StockManagement;

use App\Models\System\Catalogs\{Item};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use stdClass;

final class StockManagementConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "inventory_v2";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        return self::data([
            "warehouses" => self::data([
                "records" => CompanyReferenceDataService::for($companyId, $userId)->stockWarehouses(),
            ]),
            "products" => self::data([
                "records" => Item::query()
                    ->where("type", "product")
                    ->where("status", "active")
                    ->select(["id", "internal_code", "barcode", "name"])
                    ->orderBy("name")
                    ->get(),
            ]),
        ]);

    }
}
