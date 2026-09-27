<?php

namespace App\Observers\System\Organizations;

use App\Models\System\Organizations\{Role};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Organizations\Roles\{RolePermissionService};

class RoleObserver {
    public function saved(Role $role): void {

        $this->clear((int) $role->id);

    }

    public function deleted(Role $role): void {

        $this->clear((int) $role->id);

    }

    private function clear(int $roleId): void {

        RolePermissionService::clearRoleCache($roleId);
        CompanySectionService::clearCache($roleId);
        InitParamsCacheInvalidationService::invalidate(
            InitParamsCacheInvalidationService::ROLES
        );

    }
}
