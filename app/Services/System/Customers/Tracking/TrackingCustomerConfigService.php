<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use stdClass;

final class TrackingCustomerConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "tracking_customer";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        return self::data([
            "customers" => self::data([
                "records" => CompanyReferenceDataService::forUser($userId)->customers(),
            ]),
        ]);

    }
}
