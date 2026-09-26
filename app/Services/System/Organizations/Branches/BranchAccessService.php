<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Branches;

use App\Models\System\Organizations\{Branch, User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Tenancy\{BranchContext};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class BranchAccessService {
    public function __construct(private readonly BranchContext $context) {
    }

    public function useUser(?User $user): self {

        $this->context->setUser($user);

        return $this;

    }

    public function allowedIds(): ?array {

        return $this->context->allowedIds();

    }

    public function canAccess(int $branchId): bool {

        return $this->context->allows($branchId)
            && Branch::query()->whereKey($branchId)->exists();

    }

    public function authorize(int $branchId): Branch {

        if(!$this->canAccess($branchId)) {

            throw (new ModelNotFoundException())->setModel(Branch::class, [$branchId]);

        }

        return Branch::query()->findOrFail($branchId);

    }

    public function apply(Builder $query, string $column = "branch_id"): Builder {

        return $this->context->apply($query, $column);

    }

    public function clearFor(User $user): void {

        AccessScopeService::clearUserCache((int) $user->getKey());

        $this->context->forget();

    }
}
