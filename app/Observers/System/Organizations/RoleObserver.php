<?php

namespace App\Observers\System\Organizations;

use App\Models\System\Organizations\{Role};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Tenancy\{TenantCompanyContext};

class RoleObserver {
    public function saved(Role $role): void {

        $this->clear(app(TenantCompanyContext::class)->id(), (int) $role->id);

    }

    public function deleted(Role $role): void {

        $this->clear(app(TenantCompanyContext::class)->id(), (int) $role->id);

    }

    private function clear(int $companyId, int $roleId): void {

        RolePermissionService::clearRoleCache($companyId, $roleId);
        CompanySectionService::clearCache($companyId, $roleId);
        InitParamsCacheInvalidationService::invalidate(
            InitParamsCacheInvalidationService::ROLES
        );

    }
}
