<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Roles;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Role, RoleSubSection, User};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Organizations\{AccessScopeService, BusinessAuditService};
use Illuminate\Auth\Access\{AuthorizationException};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

final class RoleService {
    public static function query(int $companyId, string $word = ""): Builder {

        $query = Role::query()
            ->with([
                "roleSubSections:id,role_id,sub_section_id,actions",
                "branches:id,name",
                "cashRegisters:id,branch_id,name",
                "warehouses:id,branch_id,name",
            ])
            ->withCount([
                "users",
                "roleSubSections as modules_count",
            ]);

        $word = trim($word);

        if($word !== "") {

            $query->where("name", "like", "%{$word}%");

        }

        return $query->orderByDesc("is_full_access")->orderBy("name");

    }

    public static function find(int $companyId, int $roleId): Role {

        return Role::query()
            ->with([
                "roleSubSections:id,role_id,sub_section_id,actions",
                "branches:id,name",
                "cashRegisters:id,branch_id,name",
                "warehouses:id,branch_id,name",
            ])
            ->findOrFail($roleId);

    }

    public static function create(int $companyId, int $userId, array $data): Role {

        self::assertCanDelegate($companyId, $userId, $data);

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $role = Role::create([
                "slug" => Utilities::generateCode(),
                "name" => trim((string) $data["name"]),
                "is_full_access" => (bool) $data["is_full_access"],
                "branch_scope_mode" => self::scopeMode($data, "branch"),
                "cash_register_scope_mode" => self::scopeMode($data, "cash_register"),
                "warehouse_scope_mode" => self::scopeMode($data, "warehouse"),
                "status" => $data["status"],
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            self::syncPermissions($companyId, $role, $data, $userId);
            self::syncScopes($companyId, $role, $data, $userId);
            self::auditRoleSecurityChange($companyId, $role->id, $userId, [], self::roleSnapshot(self::find($companyId, $role->id)), "created");

            return self::find($companyId, $role->id);

        });

    }

    public static function update(int $companyId, int $roleId, int $userId, array $data): Role {

        self::assertCanDelegate($companyId, $userId, $data, $roleId);
        self::assertCompanyKeepsAdministrator($companyId, $roleId, $data);

        return DB::transaction(function() use ($companyId, $roleId, $userId, $data) {

            $before = self::roleSnapshot(self::find($companyId, $roleId));
            $role = Role::query()
                ->findOrFail($roleId);

            $role->update([
                "name" => trim((string) $data["name"]),
                "is_full_access" => (bool) $data["is_full_access"],
                "branch_scope_mode" => self::scopeMode($data, "branch"),
                "cash_register_scope_mode" => self::scopeMode($data, "cash_register"),
                "warehouse_scope_mode" => self::scopeMode($data, "warehouse"),
                "status" => $data["status"],
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);

            self::syncPermissions($companyId, $role, $data, $userId);
            self::syncScopes($companyId, $role, $data, $userId);
            self::auditRoleSecurityChange($companyId, $role->id, $userId, $before, self::roleSnapshot(self::find($companyId, $role->id)), "updated");

            return self::find($companyId, $role->id);

        });

    }

    public static function duplicate(int $companyId, int $roleId, int $userId, string $name): Role {

        $source = self::find($companyId, $roleId);

        $permissions = $source->is_full_access
            ? self::enabledSubSectionIds($companyId)->map(fn($subSectionId) => [
                "sub_section_id" => (int) $subSectionId,
                "actions" => RolePermissionService::actionCodes(),
            ])->values()->all()
            : $source->roleSubSections->map(fn(RoleSubSection $permission) => [
                "sub_section_id" => (int) $permission->sub_section_id,
                "actions" => $permission->actions,
            ])->values()->all();

        return self::create($companyId, $userId, [
            "name" => trim($name),
            "is_full_access" => false,
            "permissions" => $permissions,
            "branch_scope_mode" => $source->branch_scope_mode,
            "cash_register_scope_mode" => $source->cash_register_scope_mode,
            "warehouse_scope_mode" => $source->warehouse_scope_mode,
            "branch_ids" => $source->branches->pluck("id")->all(),
            "cash_register_ids" => $source->cashRegisters->pluck("id")->all(),
            "warehouse_ids" => $source->warehouses->pluck("id")->all(),
            "status" => "active",
        ]);

    }

    private static function syncPermissions(
        int $companyId,
        Role $role,
        array $data,
        int $userId
    ): void {

        $enabledIds = self::enabledSubSectionIds($companyId);
        $allActions = RolePermissionService::actionCodes();
        $permissions = collect($data["permissions"] ?? [])->map(function($permission) use ($allActions) {

            $actions = collect($permission["actions"] ?? $allActions)
                ->intersect($allActions)
                ->unique();

            if($actions->isNotEmpty() && !$actions->contains("view")) {

                $actions->prepend("view");

            }

            return [
                "sub_section_id" => (int) ($permission["sub_section_id"] ?? 0),
                "actions" => $actions->values()->all(),
            ];

        });

        if($permissions->isEmpty()) {

            $permissions = collect($data["sub_section_ids"] ?? [])->map(fn($id) => [
                "sub_section_id" => (int) $id,
                "actions" => $allActions,
            ]);

        }

        $permissions = $permissions
            ->filter(fn($permission) => $enabledIds->contains($permission["sub_section_id"]) && !empty($permission["actions"]))
            ->unique("sub_section_id")
            ->values();

        RoleSubSection::query()
            ->where("role_id", $role->id)
            ->delete();

        if($role->is_full_access || $permissions->isEmpty()) {

            RolePermissionService::clearRoleCache($companyId, (int) $role->id);
            \App\Services\System\Organizations\Companies\CompanySectionService::clearCache($companyId, (int) $role->id);

            return;

        }

        RoleSubSection::insert($permissions->map(fn($permission) => [
            "role_id" => $role->id,
            "sub_section_id" => $permission["sub_section_id"],
            "actions" => json_encode($permission["actions"]),
            "status" => "active",
            "created_at" => now(),
            "created_by" => $userId,
        ])->all());

        RolePermissionService::clearRoleCache($companyId, (int) $role->id);
        \App\Services\System\Organizations\Companies\CompanySectionService::clearCache($companyId, (int) $role->id);

    }

    private static function syncScopes(int $companyId, Role $role, array $data, int $userId): void {

        $definitions = [
            "branch" => ["table" => "role_branches", "key" => "branch_id", "resource" => "branches"],
            "cash_register" => ["table" => "role_cash_registers", "key" => "cash_register_id", "resource" => "cash_registers"],
            "warehouse" => ["table" => "role_warehouses", "key" => "warehouse_id", "resource" => "warehouses"],
        ];

        $branchIds = self::validScopeIds($companyId, "branches", $data["branch_ids"] ?? []);

        foreach($definitions as $type => $definition) {

            DB::table($definition["table"])->where("role_id", $role->id)->delete();

            if($role->is_full_access || self::scopeMode($data, $type) !== "restricted") {

                continue;

            }

            $ids = $type === "branch"
                ? $branchIds
                : self::validScopeIds(
                    $companyId,
                    $definition["resource"],
                    $data["{$type}_ids"] ?? [],
                    $branchIds
                );

            if(empty($ids)) {

                continue;

            }

            DB::table($definition["table"])->insert(array_map(fn($id) => [
                "role_id" => $role->id,
                $definition["key"] => $id,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ], $ids));

        }

        RolePermissionService::clearRoleCache($companyId, (int) $role->id);

    }

    private static function validScopeIds(
        int $companyId,
        string $table,
        array $ids,
        ?array $branchIds = null
    ): array {

        $query = DB::table($table)
            ->whereIn("id", collect($ids)->map(fn($id) => (int) $id)->filter()->unique());

        if($branchIds !== null) {

            $query->whereIn("branch_id", $branchIds);

        }

        return $query->pluck("id")->map(fn($id) => (int) $id)->values()->all();

    }

    private static function scopeMode(array $data, string $type): string {

        if((bool) ($data["is_full_access"] ?? false)) {

            return "all";

        }

        return ($data["{$type}_scope_mode"] ?? "all") === "restricted"
            ? "restricted"
            : "all";

    }

    private static function roleSnapshot(?Role $role): array {

        if(!$role) {

            return [];

        }

        return [
            "name" => $role->name,
            "is_full_access" => (bool) $role->is_full_access,
            "status" => $role->status,
            "branch_scope_mode" => $role->branch_scope_mode,
            "cash_register_scope_mode" => $role->cash_register_scope_mode,
            "warehouse_scope_mode" => $role->warehouse_scope_mode,
            "permissions" => $role->is_full_access
                ? ["full_access"]
                : $role->roleSubSections
                    ->map(fn(RoleSubSection $permission) => [
                        "sub_section_id" => (int) $permission->sub_section_id,
                        "actions" => $permission->actions,
                    ])
                    ->sortBy("sub_section_id")
                    ->values()
                    ->all(),
            "branch_ids" => $role->branches->pluck("id")->sort()->values()->all(),
            "cash_register_ids" => $role->cashRegisters->pluck("id")->sort()->values()->all(),
            "warehouse_ids" => $role->warehouses->pluck("id")->sort()->values()->all(),
        ];

    }

    private static function auditRoleSecurityChange(
        int $companyId,
        int $roleId,
        int $userId,
        array $before,
        array $after,
        string $action
    ): void {

        if($before === $after) {

            return;

        }

        BusinessAuditService::record(
            "roles",
            "security_{$action}",
            "Permisos y alcances actualizados para el perfil #{$roleId}.",
            Role::query()->find($roleId),
            $before,
            $after,
            ["affected_users" => User::query()->where("role_id", $roleId)->where("status", "active")->count()],
            null,
            $userId
        );

    }

    private static function enabledSubSectionIds(int $companyId) {

        return CompanySectionService::getSections($companyId)
            ->pluck("subSections")
            ->flatten()
            ->pluck("id")
            ->map(fn($id) => (int) $id);

    }

    private static function assertCanDelegate(
        int $companyId,
        int $actorId,
        array $data,
        ?int $targetRoleId = null
    ): void {

        $actor = User::query()
            ->with("role.roleSubSections")
            ->findOrFail($actorId);

        if($actor->role?->is_full_access) {

            return;

        }

        if((bool) ($data["is_full_access"] ?? false)) {

            throw new AuthorizationException("No puedes conceder acceso total.");

        }

        if($targetRoleId && Role::query()
            ->where("id", $targetRoleId)
            ->where("is_full_access", true)
            ->exists()) {

            throw new AuthorizationException("No puedes modificar un perfil con acceso total.");

        }

        $allActions = RolePermissionService::actionCodes();

        $actorPermissions = $actor->role->roleSubSections->mapWithKeys(fn($permission) => [
            (int) $permission->sub_section_id => collect($permission->actions ?: $allActions)->values()->all(),
        ]);

        $requestedPermissions = collect($data["permissions"] ?? []);

        if($requestedPermissions->isEmpty()) {

            $requestedPermissions = collect($data["sub_section_ids"] ?? [])->map(fn($id) => [
                "sub_section_id" => (int) $id,
                "actions" => $allActions,
            ]);

        }

        foreach($requestedPermissions as $permission) {

            $subSectionId = (int) ($permission["sub_section_id"] ?? 0);
            $allowedActions = collect($actorPermissions->get($subSectionId, []));
            $requestedActions = collect($permission["actions"] ?? $allActions);

            if($subSectionId <= 0 || $requestedActions->diff($allowedActions)->isNotEmpty()) {

                throw new AuthorizationException("No puedes conceder módulos o acciones que no posees.");

            }

        }

        $scopeDefinitions = [
            "branch" => AccessScopeService::BRANCH,
            "cash_register" => AccessScopeService::CASH_REGISTER,
            "warehouse" => AccessScopeService::WAREHOUSE,
        ];

        foreach($scopeDefinitions as $field => $scopeType) {

            $allowedIds = AccessScopeService::allowedIds($actor, $scopeType);
            $mode = (string) ($data["{$field}_scope_mode"] ?? "all");

            if($allowedIds !== null && $mode !== "restricted") {

                throw new AuthorizationException("No puedes ampliar el alcance operativo más allá de tu perfil.");

            }

            $requestedIds = collect($data["{$field}_ids"] ?? [])->map(fn($id) => (int) $id);

            if($allowedIds !== null && $requestedIds->diff($allowedIds)->isNotEmpty()) {

                throw new AuthorizationException("Seleccionaste recursos fuera de tu alcance operativo.");

            }

        }

    }

    private static function assertCompanyKeepsAdministrator(int $companyId, int $roleId, array $data): void {

        $role = Role::query()->findOrFail($roleId);
        $removesFullAccess = $role->is_full_access && (
            !(bool) ($data["is_full_access"] ?? false)
            || ($data["status"] ?? "active") !== "active"
        );

        if(!$removesFullAccess) {

            return;

        }

        $otherAdministratorExists = User::query()
            ->where("status", "active")
            ->whereHas("role", fn(Builder $query) => $query
                ->where("status", "active")
                ->where("is_full_access", true)
                ->where("id", "!=", $roleId))
            ->exists();

        if(!$otherAdministratorExists) {

            throw new AuthorizationException("La empresa debe conservar al menos un usuario con acceso total.");

        }

    }
}
