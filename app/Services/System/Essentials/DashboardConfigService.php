<?php

declare(strict_types=1);

namespace App\Services\System\Essentials;

use App\Services\System\Base\{BaseConfigService};
use stdClass;

final class DashboardConfigService extends BaseConfigService {
    protected static function getCachePrefix(): string {

        return "dashboard";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        return self::data();

    }
}
