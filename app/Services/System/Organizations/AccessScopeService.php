<?php

declare(strict_types=1);

namespace App\Services\System\Organizations;

use App\Models\System\Organizations\{Role, User};
use App\Services\System\Finance\{CashRegisterConfigService};
use App\Services\System\Purchases\{PurchaseConfigService};
use App\Services\System\Sales\{SaleConfigService};
use App\Services\System\Tenancy\{TenantContext};
use App\Services\System\Warehouses\StockManagement\{StockManagementConfigService};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{Cache, DB};
use InvalidArgumentException;

final class AccessScopeService {
    public const BRANCH = "branch";

    public const CASH_REGISTER = "cash_register";

    public const WAREHOUSE = "warehouse";

    private const CACHE_TTL = 1800;

    public static function allowedIds(User $user, string $type): ?array {

        self::assertType($type);

        $scopes = Cache::remember(
            self::cacheKey((int) $user->id),
            self::CACHE_TTL,
            fn(): array => self::resolve($user)
        );

        return $scopes[$type];

    }

    public static function canAccess(User $user, string $type, int $resourceId): bool {

        if($resourceId <= 0 || !self::existsInTenant($type, $resourceId)) {

            return false;

        }

        $allowedIds = self::allowedIds($user, $type);

        return $allowedIds === null || in_array($resourceId, $allowedIds, true);

    }

    public static function applyToQuery(Builder $query, User $user, string $type, string $column = "id"): Builder {

        $allowedIds = self::allowedIds($user, $type);

        return $allowedIds === null
            ? $query
            : $query->whereIn($column, $allowedIds);

    }

    public static function clearUserCache(int $companyId, int $userId): void {

        Cache::forget(self::cacheKey($userId));

        foreach([
            SaleConfigService::class,
            PurchaseConfigService::class,
            CashRegisterConfigService::class,
            StockManagementConfigService::class,
        ] as $configService) {

            $configService::clearUserCache($companyId, $userId);

        }

    }

    public static function clearRoleCache(int $companyId, int $roleId): void {

        User::query()
            ->where("role_id", $roleId)
            ->pluck("id")
            ->each(fn($userId) => self::clearUserCache($companyId, (int) $userId));

    }

    private static function resolve(User $user): array {

        $role = Role::query()
            ->where("status", "active")
            ->find($user->role_id);

        if(!$role) {

            return self::deniedScopes();

        }

        if($role->is_full_access) {

            return self::unrestrictedScopes();

        }

        $branchIds = self::resolveType($user, $role, self::BRANCH);

        $cashRegisterIds = self::resolveType($user, $role, self::CASH_REGISTER);
        $warehouseIds = self::resolveType($user, $role, self::WAREHOUSE);

        $cashRegisterIds = self::applyBranchHierarchy(
            self::CASH_REGISTER,
            $cashRegisterIds,
            $branchIds
        );

        $warehouseIds = self::applyBranchHierarchy(
            self::WAREHOUSE,
            $warehouseIds,
            $branchIds
        );

        return [
            self::BRANCH => $branchIds,
            self::CASH_REGISTER => $cashRegisterIds,
            self::WAREHOUSE => $warehouseIds,
        ];

    }

    private static function resolveType(User $user, Role $role, string $type): ?array {

        $definition = self::definition($type);
        $roleMode = (string) ($role->{$definition["role_mode"]} ?? "all");
        $roleIds = $roleMode === "all"
            ? null
            : self::pivotIds($definition["role_table"], "role_id", (int) $role->id, $definition["resource_key"]);

        $userMode = (string) ($user->{$definition["user_mode"]} ?? "inherit");

        if($userMode !== "restricted") {

            return $roleIds;

        }

        $userIds = self::pivotIds($definition["user_table"], "user_id", (int) $user->id, $definition["resource_key"]);

        return $roleIds === null
            ? $userIds
            : array_values(array_intersect($roleIds, $userIds));

    }

    private static function applyBranchHierarchy(
        string $type,
        ?array $resourceIds,
        ?array $branchIds
    ): ?array {

        if($branchIds === null) {

            return $resourceIds;

        }

        if($branchIds === []) {

            return [];

        }

        $definition = self::definition($type);

        $query = DB::table($definition["resource_table"])
            ->whereIn("branch_id", $branchIds);

        if($resourceIds !== null) {

            $query->whereIn("id", $resourceIds);

        }

        return $query->pluck("id")->map(fn($id) => (int) $id)->values()->all();

    }

    private static function pivotIds(string $table, string $ownerKey, int $ownerId, string $resourceKey): array {

        return DB::table($table)
            ->where($ownerKey, $ownerId)
            ->where("status", "active")
            ->pluck($resourceKey)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

    }

    private static function existsInTenant(string $type, int $resourceId): bool {

        $definition = self::definition($type);

        return DB::table($definition["resource_table"])
            ->where("id", $resourceId)
            ->exists();

    }

    private static function definition(string $type): array {

        return match ($type) {
            self::BRANCH => [
                "resource_table" => "branches",
                "resource_key" => "branch_id",
                "role_table" => "role_branches",
                "user_table" => "user_branches",
                "role_mode" => "branch_scope_mode",
                "user_mode" => "branch_scope_mode",
            ],
            self::CASH_REGISTER => [
                "resource_table" => "cash_registers",
                "resource_key" => "cash_register_id",
                "role_table" => "role_cash_registers",
                "user_table" => "user_cash_registers",
                "role_mode" => "cash_register_scope_mode",
                "user_mode" => "cash_register_scope_mode",
            ],
            self::WAREHOUSE => [
                "resource_table" => "warehouses",
                "resource_key" => "warehouse_id",
                "role_table" => "role_warehouses",
                "user_table" => "user_warehouses",
                "role_mode" => "warehouse_scope_mode",
                "user_mode" => "warehouse_scope_mode",
            ],
            default => throw new InvalidArgumentException("Tipo de alcance no soportado.")
        };

    }

    private static function assertType(string $type): void {

        self::definition($type);

    }

    private static function cacheKey(int $userId): string {

        return app(TenantContext::class)->cacheNamespace().":access_scopes:user:{$userId}";

    }

    private static function unrestrictedScopes(): array {

        return [self::BRANCH => null, self::CASH_REGISTER => null, self::WAREHOUSE => null];

    }

    private static function deniedScopes(): array {

        return [self::BRANCH => [], self::CASH_REGISTER => [], self::WAREHOUSE => []];

    }
}
