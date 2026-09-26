<?php

declare(strict_types=1);

namespace App\Services\System\Assets\Assets;

use App\Models\System\Assets\{Asset, AssetCategory};
use App\Services\System\Base\{BaseConfigService};
use stdClass;

final class AssetConfigService extends BaseConfigService {
    protected static function getCachePrefix(): string {

        return "asset";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        return self::data([
            "internal_code_prefixes" => self::internalCodePrefixes(),
            "categories" => AssetCategory::query()
                ->where("status", "active")
                ->orderBy("name")
                ->get(["id", "name"]),
            "statuses" => Asset::getStatuses(),
        ]);

    }
}
