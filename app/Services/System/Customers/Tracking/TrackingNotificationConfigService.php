<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Services\System\Base\{BaseConfigService};
use stdClass;

final class TrackingNotificationConfigService extends BaseConfigService {
    protected static function getCachePrefix(): string {

        return "tracking_notification";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        return self::data();

    }
}
