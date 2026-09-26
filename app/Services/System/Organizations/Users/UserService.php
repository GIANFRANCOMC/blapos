<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Users;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Organizations\{Role, User};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Organizations\{AccessScopeService, BusinessAuditService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Auth\Access\{AuthorizationException};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Str};

/**
 * Service class for managing module operations
 * Handles business logic for creating and updating records
 */
class UserService {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Organizations.user";

    /**
     * Allowed fields for record creation and update
     */
    private const ALLOWED_FIELDS = [
        "role_id",
        "branch_scope_mode",
        "cash_register_scope_mode",
        "warehouse_scope_mode",
        "identity_document_type_id",
        "document_number",
        "name",
        "email",
        "phone_number",
        "gender",
        "birthdate",
        "status",
    ];

    /**
     * Searchable fields for filtering
     */
    private const SEARCHABLE_FIELDS = [
        "document_number",
        "name",
        "email",
        "phone_number",
    ];

    /**
     * Get translation with fallback
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    private static function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback(self::TRANSLATION_NAMESPACE, $key, $replace);

    }

    /**
     * Prepare data for creation
     *
     * @param  array  $data Input data
     * @param  int  $companyId Company
     * @param  int  $userId User
     */
    private static function prepareUserDataForCreate(array $data, int $companyId, int $userId): array {

        $userData = [
            "gender" => $data["gender"] ?? "other",
            "status" => $data["status"] ?? "active",
            "created_at" => now(),
            "created_by" => $userId,
        ];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(isset($data[$field])) {

                $userData[$field] = $field === "email" ? Str::lower($data[$field]) : $data[$field];

            }

        }

        // Handle password separately (it's hashed automatically by Laravel)

        if(isset($data["password"])) {

            $userData["password"] = $data["password"];

        }

        return $userData;

    }

    /**
     * Prepare data for update (only changed fields)
     *
     * @param  User  $user Record instance
     * @param  array  $data Input data
     */
    private static function prepareUserDataForUpdate(User $user, array $data): array {

        $updateData = [];

        foreach(self::ALLOWED_FIELDS as $field) {

            if(isset($data[$field])) {

                $value = $field === "email" ? Str::lower($data[$field]) : $data[$field];

                if($value !== $user->$field) {

                    $updateData[$field] = $value;

                }

            }

        }

        return $updateData;

    }

    /**
     * Create a new record
     *
     * @param  array  $data Input data
     * @param  int|null  $userId User creating the record
     * @return User|null Created record instance or null on failure
     */
    public static function create(array $data, int $companyId, int $userId): ?User {

        $user = null;

        DB::transaction(function() use ($data, $companyId, $userId, &$user) {

            self::assertRoleAssignable($companyId, (int) $userId, (int) ($data["role_id"] ?? 0));

            // Prepare data with only allowed fields
            $userData = self::prepareUserDataForCreate($data, $companyId, $userId);

            // Create the record

            $user = User::create($userData);
            self::syncBranches($user, $data["branch_ids"] ?? [], $companyId, $userId);
            self::syncResourceScopes($user, $data, $companyId, $userId);

        });

        return $user?->fresh(["identityDocumentType", "role", "branches", "cashRegisters", "warehouses"]);

    }

    /**
     * Update an existing record
     *
     * @param  User  $user Record instance to update
     * @param  array  $data Input data
     * @param  int|null  $userId User updating the record
     * @return User Updated record instance
     */
    public static function update(User $user, array $data, int $userId): User {

        DB::transaction(function() use ($user, $data, $userId) {

            $companyId = app(TenantCompanyContext::class)->id();

            self::assertRoleAssignable(
                $companyId,
                (int) $userId,
                (int) ($data["role_id"] ?? $user->role_id)
            );

            $sensitiveBefore = self::sensitiveSnapshot($user);

            // Prepare update data with only changed fields
            $updateData = self::prepareUserDataForUpdate($user, $data);

            // Only update if there are changes

            if(!empty($updateData)) {

                $invalidateSessions = array_key_exists("status", $updateData)
                    && $updateData["status"] !== "active";

                if($invalidateSessions) {

                    $updateData["session_version"] = max(1, (int) ($user->session_version ?? 1)) + 1;
                    $updateData["remember_token"] = null;

                }

                $updateData["updated_at"] = now();

                $updateData["updated_by"] = $userId;
                $user->update($updateData);

                if($invalidateSessions) {

                    $user->tokens()->delete();

                }

            }

            self::syncBranches($user, $data["branch_ids"] ?? [], $companyId, $userId);
            self::syncResourceScopes($user, $data, $companyId, $userId);

            self::auditSensitiveChange(
                $companyId,
                $user,
                $userId,
                $sensitiveBefore,
                self::sensitiveSnapshot($user->fresh(["branches", "cashRegisters", "warehouses"]))
            );

        });

        return $user->fresh(["identityDocumentType", "role", "branches", "cashRegisters", "warehouses"]);

    }

    public static function changePassword(User $user, string $password, ?int $userId = null): User {

        DB::transaction(function() use ($user, $password, $userId) {

            $user->forceFill([
                "password" => $password,
                "remember_token" => null,
                "session_version" => max(1, (int) ($user->session_version ?? 1)) + 1,
                "updated_at" => now(),
                "updated_by" => $userId,
            ])->save();

            $user->tokens()->delete();

            BusinessAuditService::record(
                "users",
                "password_changed",
                "Contraseña actualizada para el colaborador #{$user->id}.",
                $user,
                [],
                ["session_version" => $user->session_version],
                ["target_user_id" => (int) $user->id],
                null,
                $userId
            );

        });

        return $user->fresh();

    }

    private static function syncBranches(User $user, array $branchIds, int $companyId, ?int $userId = null): void {

        DB::table("user_branches")
            ->where("user_id", $user->id)
            ->delete();

        $branchIds = collect($branchIds)
            ->filter()
            ->map(fn($branchId) => (int) $branchId)
            ->unique()
            ->values()
            ->all();

        if(empty($branchIds)) {

            return;

        }

        $validBranchIds = DB::table("branches")
            ->whereIn("id", $branchIds)
            ->pluck("id")
            ->map(fn($branchId) => (int) $branchId)
            ->all();

        if(empty($validBranchIds)) {

            return;

        }

        $now = now();

        DB::table("user_branches")->insert(array_map(fn($branchId) => [
            "user_id" => $user->id,
            "branch_id" => $branchId,
            "status" => "active",
            "created_at" => $now,
            "created_by" => $userId,
        ], $validBranchIds));

    }

    private static function assertRoleAssignable(int $companyId, int $actorId, int $roleId): void {

        $actor = User::query()->findOrFail($actorId);
        $role = Role::query()->findOrFail($roleId);

        if(!RolePermissionService::canAssignRole($actor, $role)) {

            throw new AuthorizationException("No puedes asignar un perfil con permisos superiores a los tuyos.");

        }

    }

    private static function syncResourceScopes(
        User $user,
        array $data,
        int $companyId,
        ?int $userId = null
    ): void {

        $definitions = [
            "cash_register" => ["table" => "user_cash_registers", "resource" => "cash_registers", "key" => "cash_register_id"],
            "warehouse" => ["table" => "user_warehouses", "resource" => "warehouses", "key" => "warehouse_id"],
        ];

        $branchIds = collect($data["branch_ids"] ?? [])->map(fn($id) => (int) $id)->filter()->all();

        foreach($definitions as $type => $definition) {

            DB::table($definition["table"])
                ->where("user_id", $user->id)
                ->delete();

            $ids = collect($data["{$type}_ids"] ?? [])->map(fn($id) => (int) $id)->filter()->unique();

            if($ids->isEmpty()) {

                continue;

            }

            $validIds = DB::table($definition["resource"])
                ->whereIn("id", $ids)
                ->when(!empty($branchIds), fn($query) => $query->whereIn("branch_id", $branchIds))
                ->pluck("id")
                ->map(fn($id) => (int) $id)
                ->all();

            DB::table($definition["table"])->insert(array_map(fn($id) => [
                "user_id" => $user->id,
                $definition["key"] => $id,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ], $validIds));

        }

        AccessScopeService::clearUserCache((int) $user->id);

    }

    private static function sensitiveSnapshot(User $user): array {

        return [
            "role_id" => $user->role_id,
            "branch_scope_mode" => $user->branch_scope_mode,
            "cash_register_scope_mode" => $user->cash_register_scope_mode,
            "warehouse_scope_mode" => $user->warehouse_scope_mode,
            "status" => $user->status,
            "session_version" => $user->session_version,
            "branch_ids" => $user->relationLoaded("branches") ? $user->branches->pluck("id")->sort()->values()->all() : [],
            "cash_register_ids" => $user->relationLoaded("cashRegisters") ? $user->cashRegisters->pluck("id")->sort()->values()->all() : [],
            "warehouse_ids" => $user->relationLoaded("warehouses") ? $user->warehouses->pluck("id")->sort()->values()->all() : [],
        ];

    }

    private static function auditSensitiveChange(
        int $companyId,
        User $user,
        int $actorId,
        array $before,
        array $after
    ): void {

        if($before === $after) {

            return;

        }

        BusinessAuditService::record(
            "users",
            "security_updated",
            "Seguridad actualizada para el colaborador #{$user->id}.",
            $user,
            $before,
            $after,
            ["target_user_id" => (int) $user->id],
            null,
            $actorId
        );

    }

    /**
     * Find record by ID and company ID
     *
     * @param  int  $id Record
     * @param  int  $companyId Company
     * @param  array|null  $statuses Filter by statuses (e.g. ["active"], ["active", "inactive"])
     * @param  array  $relations Relations to eager load
     */
    public static function findByIdInTenant(int $id, int $companyId, ?array $statuses = ["active"], array $relations = ["identityDocumentType", "role", "branches", "cashRegisters", "warehouses"]): ?User {

        $query = User::where("id", $id);

        if($statuses !== null && !empty($statuses)) {

            $query->whereIn("status", $statuses);

        }

        if($relations !== null && !empty($relations)) {

            $query->with($relations);

        }

        return $query->first();

    }

    /**
     * Get paginated list of records with filters
     *
     * @param  int  $companyId Company
     * @param  array  $filters Filter parameters (filter_by, word)
     * @param  int  $perPage Items per page
     */
    public static function getPaginatedList(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator {

        $query = User::query()
            ->with(["identityDocumentType", "role", "branches", "cashRegisters", "warehouses"]);

        // Apply filters

        $filterBy = $filters["filter_by"] ?? null;

        $word = $filters["word"] ?? null;

        if(Utilities::isDefined($word) && Utilities::isDefined($filterBy)) {

            $searchTerm = Utilities::getWordSearch($word);

            if($filterBy === "all") {

                // Search across all searchable fields
                $query->where(function(Builder $q) use ($searchTerm) {

                    $searchableFields = self::SEARCHABLE_FIELDS;
                    $firstField = array_shift($searchableFields);

                    if($firstField) {

                        $q->where($firstField, "like", $searchTerm);

                    }

                    foreach($searchableFields as $field) {

                        $q->orWhere($field, "like", $searchTerm);

                    }

                });

            }elseif(in_array($filterBy, self::SEARCHABLE_FIELDS, true)) {

                // Search in specific field
                $query->where($filterBy, "like", $searchTerm);

            }

        }

        return $query->orderBy("name", "ASC")
            ->paginate($perPage);

    }
}
