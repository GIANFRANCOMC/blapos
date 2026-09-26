<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Models\System\Organizations\{Company};
use App\Services\System\Base\{BaseConfigService, MasterReferenceDataService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use stdClass;

final class CompanyConfigService extends BaseConfigService {
    protected static function getCachePrefix(): string {

        return "company";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        $config = self::data([
            "statuses" => Company::getStatuses(),
            "identityDocumentTypes" => self::data([
                "records" => MasterReferenceDataService::companyIdentityDocuments(),
            ]),
        ]);

        $company = app(TenantCompanyContext::class)->get()->load("socialsMedia");

        if(!$company) {

            return $config;

        }

        $socialsMedia = $company->socialsMedia->keyBy("type");

        $company->facebook = $socialsMedia->get("facebook")?->link;
        $company->instagram = $socialsMedia->get("instagram")?->link;
        $company->whatsapp = $socialsMedia->get("whatsapp")?->link;

        $config->company = self::data([
            "records" => [$company],
        ]);

        return $config;

    }
}
