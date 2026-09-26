<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Subscriptions;

use App\Models\System\Catalogs\{Item};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService, MasterReferenceDataService};
use stdClass;

final class SubscriptionConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "subscription";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::forUser($userId);

        return self::data([
            "categories" => self::data([
                "records" => $references->categories(),
            ]),
            "currencies" => self::data([
                "records" => MasterReferenceDataService::currencies(),
            ]),
            "internal_code_prefixes" => self::internalCodePrefixes(),
            "durationTypes" => Item::getDurationTypes(),
            "statuses" => Item::getStatuses(),
        ]);

    }
}
