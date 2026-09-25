<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Roles;

use App\Models\System\General\{SubSection};
use App\Models\System\Organizations\{Role, User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Tenancy\{TenantCompanyContext, TenantContext};
use Illuminate\Support\Facades\{Cache};

final class RolePermissionService {
    private const CACHE_TTL = 1800;

    private const CACHE_PREFIX = "role_permissions";

    private const PUBLIC_ROUTE_PREFIXES = ["account", "workspace", "home", "helpers", "logout"];

    public static function canAccessRoute(
        User $user,
        ?string $routeName,
        string $httpMethod = "GET",
        ?string $context = null
    ): bool {

        if(!$routeName) {

            return true;

        }

        $prefix = self::routePrefix($routeName);

        if(in_array($prefix, self::PUBLIC_ROUTE_PREFIXES, true)) {

            return true;

        }

        if(!$user->role_id) {

            return false;

        }

        $permissions = self::getPermissions(
            app(TenantCompanyContext::class)->id(),
            (int) $user->role_id
        );

        $action = self::actionForRoute($routeName, $httpMethod);
        $candidates = config("permissions.route_modules.{$routeName}");

        if($routeName === "sales.store" && $context === "pos") {

            $action = "operate";
            $candidates = ["sales.pos"];

        }

        if(!is_array($candidates)) {

            $candidates = in_array($routeName, $permissions["catalog_routes"], true)
                ? [$routeName]
                : collect(array_keys($permissions["modules"]))
                    ->filter(fn($moduleRoute) => self::routePrefix($moduleRoute) === $prefix)
                    ->values()
                    ->all();

        }

        return collect($candidates)->contains(function($moduleRoute) use ($permissions, $action) {

            $allowedActions = $permissions["modules"][$moduleRoute] ?? null;

            return is_array($allowedActions) && in_array($action, $allowedActions, true);

        });

    }

    public static function actionForRoute(string $routeName, string $httpMethod = "GET"): string {

        $configuredAction = config("permissions.route_actions.{$routeName}");

        if(is_string($configuredAction) && in_array($configuredAction, self::actionCodes(), true)) {

            return $configuredAction;

        }

        $segments = array_map("strtolower", explode(".", $routeName));
        array_shift($segments);

        foreach(["export", "import", "delete"] as $action) {

            if(self::containsRouteToken($segments, $action)) {

                return $action;

            }

        }

        if(in_array(strtoupper($httpMethod), ["GET", "HEAD"], true)) {

            if(in_array("pos", $segments, true)) {

                return "operate";

            }

            if(in_array("create", $segments, true)) {

                return "create";

            }

            if(in_array("edit", $segments, true)) {

                return "update";

            }

            return "view";

        }

        if(self::containsRouteToken($segments, "operate")) {

            return "operate";

        }

        return match (strtoupper($httpMethod)) {
            "DELETE" => "delete",
            "PUT", "PATCH" => "update",
            "POST" => self::containsRouteToken($segments, "update") ? "update" : "create",
            default => "view"
        };

    }

    private static function containsRouteToken(array $segments, string $action): bool {

        $tokens = config("permissions.route_tokens.{$action}", []);

        return collect($segments)->contains(fn($segment) => in_array($segment, $tokens, true));

    }

    public static function actionCodes(): array {

        return collect(config("permissions.actions", []))->pluck("code")->values()->all();

    }

    public static function allowedActionsBySubSection(User $user): array {

        $role = Role::query()
            ->with("roleSubSections")
            ->find($user->role_id);

        if(!$role || $role->is_full_access) {

            return [];

        }

        $allActions = self::actionCodes();

        return $role->roleSubSections->mapWithKeys(fn($permission) => [
            (int) $permission->sub_section_id => collect($permission->actions ?: $allActions)
                ->intersect($allActions)
                ->values()
                ->all(),
        ])->all();

    }

    public static function allowedSubSectionIds(int $companyId, int $roleId): array {

        $permissions = self::getPermissions($companyId, $roleId);

        return $permissions["is_full_access"] ? [] : $permissions["sub_section_ids"];

    }

    public static function isFullAccess(int $companyId, int $roleId): bool {

        return self::getPermissions($companyId, $roleId)["is_full_access"];

    }

    public static function canAssignRole(User $actor, Role $targetRole): bool {

        $companyId = app(TenantCompanyContext::class)->id();
        $actorPermissions = self::getPermissions($companyId, (int) $actor->role_id);

        if($actorPermissions["is_full_access"]) {

            return true;

        }

        $targetPermissions = self::getPermissions($companyId, (int) $targetRole->id);

        if($targetPermissions["is_full_access"]) {

            return false;

        }

        foreach($targetPermissions["modules"] as $moduleRoute => $actions) {

            $actorActions = $actorPermissions["modules"][$moduleRoute] ?? [];

            if(array_diff($actions, $actorActions)) {

                return false;

            }

        }

        $targetRole->loadMissing(["branches:id", "cashRegisters:id", "warehouses:id"]);

        $scopes = [
            [AccessScopeService::BRANCH, "branch_scope_mode", "branches"],
            [AccessScopeService::CASH_REGISTER, "cash_register_scope_mode", "cashRegisters"],
            [AccessScopeService::WAREHOUSE, "warehouse_scope_mode", "warehouses"],
        ];

        foreach($scopes as [$type, $modeField, $relation]) {

            $actorIds = AccessScopeService::allowedIds($actor, $type);

            if($actorIds === null) {

                continue;

            }

            if(($targetRole->{

                $modeField

            } ?? "all") !== "restricted") {

                return false;

            }

            $targetIds = $targetRole->{$relation}->pluck("id")->map(fn($id) => (int) $id)->all();

            if(array_diff($targetIds, $actorIds)) {

                return false;

            }

        }

        return true;

    }

    public static function clearRoleCache(int $companyId, int $roleId): void {

        Cache::forget(self::cacheKey($companyId, $roleId));
        AccessScopeService::clearRoleCache($companyId, $roleId);

    }

    public static function clearCompanyCache(int $companyId): void {

        Role::query()
            ->pluck("id")
            ->each(fn($roleId) => self::clearRoleCache($companyId, (int) $roleId));

    }

    public static function cacheKey(int $companyId, int $roleId): string {

        return app(TenantContext::class)->cacheNamespace().":".self::CACHE_PREFIX.":role:{$roleId}";

    }

    private static function getPermissions(int $companyId, int $roleId): array {

        return Cache::remember(
            self::cacheKey($companyId, $roleId),
            self::CACHE_TTL,
            fn() => self::queryPermissions($companyId, $roleId)
        );

    }

    private static function queryPermissions(int $companyId, int $roleId): array {

        $role = Role::query()
            ->where("status", "active")
            ->with(["roleSubSections.subSection" => function($query) use ($companyId) {

                $query->whereHas("companiesSubSections", function($companyQuery) use ($companyId) {

                    $companyQuery->where("company_id", $companyId)->where("status", "active");

                });

            }])
            ->find($roleId);

        if(!$role) {

            return self::deniedPermissions();

        }

        $allActions = self::actionCodes();

        $enabledSubSections = $role->is_full_access
            ? SubSection::query()
                ->where("status", "active")
                ->whereHas("companiesSubSections", function($query) use ($companyId): void {

                    $query->where("company_id", $companyId)->where("status", "active");

                })
                ->get()
            : $role->roleSubSections
                ->filter(fn($permission) => $permission->subSection)
                ->pluck("subSection");

        $modulePermissions = $enabledSubSections
            ->mapWithKeys(function($subSection) use ($allActions, $role) {

                $moduleRoute = (string) $subSection->dom_route;
                $permission = $role->is_full_access
                    ? null
                    : $role->roleSubSections->firstWhere("sub_section_id", $subSection->id);

                $actions = collect($permission?->actions ?: $allActions)
                    ->intersect($allActions)
                    ->unique()
                    ->values()
                    ->all();

                return $moduleRoute !== "" ? [$moduleRoute => $actions] : [];

            })
            ->all();

        return [
            "is_full_access" => (bool) $role->is_full_access,
            "catalog_routes" => SubSection::query()
                ->where("status", "active")
                ->where("dom_route", "!=", "")
                ->pluck("dom_route")
                ->values()
                ->all(),
            "sub_section_ids" => $enabledSubSections
                ->pluck("id")
                ->map(fn($id) => (int) $id)
                ->values()
                ->all(),
            "modules" => $modulePermissions,
        ];

    }

    private static function deniedPermissions(): array {

        return ["is_full_access" => false, "catalog_routes" => [], "sub_section_ids" => [], "modules" => []];

    }

    private static function routePrefix(string $routeName): string {

        return explode(".", $routeName)[0] ?? $routeName;

    }
}
