<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\{AccessScopeService};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{Auth};
use RuntimeException;

final class BranchContext {
    private bool $resolved = false;

    private ?int $userId = null;

    private ?array $allowedIds = null;

    public function setUser(?User $user): void {

        $this->resolved = true;
        $this->userId = $user?->getKey() ? (int) $user->getKey() : null;
        $this->allowedIds = $user
            ? AccessScopeService::allowedIds($user, AccessScopeService::BRANCH)
            : null;

    }

    public function allowedIds(): ?array {

        $this->resolveAuthenticatedUser();

        return $this->allowedIds;

    }

    public function userId(): ?int {

        $this->resolveAuthenticatedUser();

        return $this->userId;

    }

    public function allows(int $branchId): bool {

        if($branchId <= 0) {

            return false;

        }

        $allowedIds = $this->allowedIds();

        return $allowedIds === null || in_array($branchId, $allowedIds, true);

    }

    public function authorize(int $branchId): void {

        if(!$this->allows($branchId)) {

            throw new RuntimeException("No tienes acceso a la sucursal seleccionada.");

        }

    }

    public function apply(Builder $query, string $column = "branch_id"): Builder {

        $allowedIds = $this->allowedIds();

        return $allowedIds === null
            ? $query
            : $query->whereIn($column, $allowedIds);

    }

    public function forget(): void {

        $this->resolved = false;
        $this->userId = null;
        $this->allowedIds = null;

    }

    private function resolveAuthenticatedUser(): void {

        if($this->resolved) {

            return;

        }

        $user = Auth::user();

        $this->setUser($user instanceof User ? $user : null);

    }
}
