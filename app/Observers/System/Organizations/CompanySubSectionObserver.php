<?php

declare(strict_types=1);

namespace App\Observers\System\Organizations;

use App\Models\System\Organizations\{CompanySubSection};
use App\Services\System\Organizations\Companies\{CompanySectionService};

final class CompanySubSectionObserver {
    public function saved(CompanySubSection $companySubSection): void {

        CompanySectionService::clearTenantCache();

    }

    public function deleted(CompanySubSection $companySubSection): void {

        CompanySectionService::clearTenantCache();

    }
}
