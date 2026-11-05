<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\StockManagement;

use App\Models\System\Catalogs\{Brand, Category, Item};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use stdClass;

final class StockManagementConfigService extends BaseConfigService {

    protected const USER_SCOPED_CACHE = true;

    protected static function cachePages(): array {

        return ["main", "stock", "transfers", "guides", "kardex", "valued"];

    }

    protected static function getCachePrefix(): string {

        return "inventory";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        return self::data([
            "warehouses" => self::data([
                "records" => CompanyReferenceDataService::forUser($userId)->stockWarehouses(),
            ]),
            "products" => self::data([
                "records" => in_array($page, ["main", "stock", "transfers"], true)
                    ? Item::query()
                        ->where("type", "product")
                        ->where("status", "active")
                        ->with("measurementUnit:id,abbreviation")
                        ->select(["id", "measurement_unit_id", "internal_code", "barcode", "name"])
                        ->orderBy("name")
                        ->get()
                    : [],
            ]),
            "stock_categories" => self::data([
                "records" => $page === "stock"
                    ? Category::query()->active()->orderBy("name")->get(["id", "name"])
                    : [],
            ]),
            "stock_brands" => self::data([
                "records" => $page === "stock"
                    ? Brand::query()->active()->orderBy("name")->get(["id", "name"])
                    : [],
            ]),
        ]);

    }

}
