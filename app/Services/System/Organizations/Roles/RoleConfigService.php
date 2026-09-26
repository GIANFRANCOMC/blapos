<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Roles;

use App\Models\System\Organizations\{Role, User};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use stdClass;

final class RoleConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "roles";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        $user = $userId
            ? User::query()->find($userId)
            : null;

        $sections = CompanySectionService::getSections(
            app(TenantCompanyContext::class)->id(),
            $user?->role_id
        );

        $delegableActions = $user ? RolePermissionService::allowedActionsBySubSection($user) : [];
        $references = CompanyReferenceDataService::forUser($userId);

        $sections->each(function($section) use ($delegableActions): void {

            $section->subSections->each(function($subSection) use ($delegableActions): void {

                $subSection->setAttribute(
                    "delegable_actions",
                    $delegableActions[(int) $subSection->id] ?? null
                );

            });

        });

        return self::data([
            "sections" => self::data([
                "records" => $sections,
            ]),
            "permissionActions" => config("permissions.actions", []),
            "branches" => $references->activeBranches(),
            "cashRegisters" => $references->cashRegisters(),
            "warehouses" => $references->stockWarehouses(),
            "statuses" => Role::getStatuses(),
        ]);

    }
}
