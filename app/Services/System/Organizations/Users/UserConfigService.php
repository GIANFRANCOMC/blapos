<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Users;

use App\Models\System\Organizations\{User};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService, MasterReferenceDataService};
use stdClass;

final class UserConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "user";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::forUser($userId);

        return self::data([
            "identityDocumentTypes" => self::data([
                "records" => MasterReferenceDataService::defaultIdentityDocuments($companyId),
            ]),
            "roles" => self::data([
                "records" => $references->roles(),
            ]),
            "branches" => self::data([
                "records" => $references->activeBranches(),
            ]),
            "cashRegisters" => self::data([
                "records" => $references->cashRegisters(),
            ]),
            "warehouses" => self::data([
                "records" => $references->stockWarehouses(),
            ]),
            "genders" => User::getGenders(),
            "statuses" => User::getStatuses(),
        ]);

    }
}
