<?php

namespace App\Observers\System\Organizations;

use App\Models\System\Organizations\{RoleSubSection};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Tenancy\{TenantCompanyContext};

class RoleSubSectionObserver {
    public function saved(RoleSubSection $permission): void {

        $this->clear($permission);

    }

    public function deleted(RoleSubSection $permission): void {

        $this->clear($permission);

    }

    private function clear(RoleSubSection $permission): void {

        $role = $permission->role;

        if(!$role) {

            return;

        }

        $companyId = app(TenantCompanyContext::class)->id();

        RolePermissionService::clearRoleCache($companyId, (int) $role->id);
        CompanySectionService::clearCache($companyId, (int) $role->id);

    }
}
