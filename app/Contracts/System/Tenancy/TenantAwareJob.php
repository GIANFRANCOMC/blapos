<?php

declare(strict_types=1);

namespace App\Contracts\System\Tenancy;

interface TenantAwareJob {
    public function tenantDatabaseId(): int;
}
