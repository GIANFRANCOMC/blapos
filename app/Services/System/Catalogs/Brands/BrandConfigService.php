<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Brands;

use App\Models\System\Catalogs\{Brand};
use App\Services\System\Base\{BaseConfigService};
use stdClass;

final class BrandConfigService extends BaseConfigService {
    protected static function getCachePrefix(): string {

        return "brand";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        return self::data([
            "internal_code_prefixes" => self::internalCodePrefixes(),
            "statuses" => Brand::getStatuses(),
        ]);

    }
}
