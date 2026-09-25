<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Customers;

use App\Models\System\Customers\{Customer};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService, MasterReferenceDataService};
use stdClass;

final class CustomerConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "customer";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::forUser($userId);

        return self::data([
            "biometricDevices" => self::data([
                "records" => $references->biometricDevices(),
            ]),
            "identityDocumentTypes" => self::data([
                "records" => MasterReferenceDataService::customerIdentityDocuments($companyId),
            ]),
            "genders" => Customer::getGenders(),
            "statuses" => Customer::getStatuses(),
        ]);

    }
}
