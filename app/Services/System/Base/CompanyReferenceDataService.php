<?php

declare(strict_types=1);

namespace App\Services\System\Base;

use App\Models\System\Assets\{Asset};
use App\Models\System\Catalogs\{Brand, Category, Item};
use App\Models\System\Customers\{Customer};
use App\Models\System\Devices\{BiometricDevice};
use App\Models\System\Finance\{CashRegister, PaymentMethod, Tax};
use App\Models\System\Organizations\{Branch, Role, User};
use App\Models\System\Sales\{SaleDeliveryMethod};
use App\Models\System\Warehouses\{Warehouse};
use App\Services\System\Organizations\{AccessScopeService};
use Illuminate\Database\Eloquent\{Builder, Collection};

/**
 * Provides reusable tenant records used by module initParams.
 */
final class CompanyReferenceDataService {
    private bool $userResolved = false;

    private ?User $user = null;

    /** @var array<string, array<int, int>|null> */
    private array $allowedIdsCache = [];

    private function __construct(private readonly ?int $userId = null) {
    }

    public static function forUser(?int $userId = null): self {

        return new self($userId);

    }

    public function categories(): Collection {

        return Category::query()
            ->where("status", "active")
            ->orderBy("name")
            ->get();

    }

    public function brands(): Collection {

        return Brand::query()
            ->where("status", "active")
            ->orderBy("name")
            ->get();

    }

    public function stockWarehouses(): Collection {

        $query = Warehouse::query()
            ->with("branch")
            ->where("status", "active");

        $warehouseIds = $this->allowedWarehouseIds();

        if($warehouseIds !== null) {

            $query->whereIn("id", $warehouseIds);

        }

        return $query->orderBy("name")->get();

    }

    public function activeBranches(): Collection {

        return $this->branchQuery()
            ->where("status", "active")
            ->get();

    }

    public function branchesWithSeries(): Collection {

        return $this->branchQuery()
            ->where("status", "active")
            ->with("series.documentType")
            ->get();

    }

    public function cashRegisters(): Collection {

        $query = CashRegister::query()
            ->with("branch")
            ->where("status", "active");

        $cashRegisterIds = $this->allowedCashRegisterIds();

        if($cashRegisterIds !== null) {

            $query->whereIn("id", $cashRegisterIds);

        }

        return $query->orderBy("name")->get();

    }

    public function customers(): Collection {

        return $this->customerQuery()->get();

    }

    public function activeCustomers(): Collection {

        return $this->customerQuery()
            ->where("status", "active")
            ->get();

    }

    public function saleItems(): Collection {

        Item::expireActiveItems();

        return Item::query()
            ->availableForSale()
            ->with(["currency", "brand", "categoryItems.category", "warehouseItems.warehouse"])
            ->orderBy("type")
            ->orderBy("name")
            ->get();

    }

    public function subscriptionItems(): Collection {

        Item::expireActiveItems();

        return Item::query()
            ->where("type", "subscription")
            ->availableForSale()
            ->with("currency")
            ->orderBy("name")
            ->get();

    }

    public function roles(): Collection {

        return Role::query()
            ->where("status", "active")
            ->orderBy("name")
            ->get();

    }

    public function taxesFor(string $scope): Collection {

        return Tax::query()
            ->whereIn("scope", [$scope, "both"])
            ->where("status", "active")
            ->orderByDesc("is_default")
            ->orderBy("name")
            ->get();

    }

    public function paymentMethodsFor(string $scope): Collection {

        return PaymentMethod::query()
            ->with(["variants" => fn($query) => $query->orderBy("name")])
            ->whereIn("scope", [$scope, "both"])
            ->where("status", "active")
            ->orderByDesc("is_default")
            ->orderBy("name")
            ->get();

    }

    public function saleDeliveryMethods(): Collection {

        return SaleDeliveryMethod::query()
            ->where("status", "active")
            ->orderByDesc("is_default")
            ->orderBy("sort_order")
            ->orderBy("name")
            ->get();

    }

    public function assets(): Collection {

        return Asset::query()
            ->where("status", "active")
            ->orderBy("name")
            ->get();

    }

    public function biometricDevices(): Collection {

        $query = BiometricDevice::query()
            ->where("status", "active");

        $branchIds = $this->allowedBranchIds();

        if($branchIds !== null) {

            $query->whereIn("branch_id", $branchIds);

        }

        return $query->orderBy("name")->get();

    }

    public function users(): Collection {

        return User::query()
            ->where("status", "active")
            ->with("identityDocumentType")
            ->orderBy("name")
            ->get();

    }

    public function userOptions(): Collection {

        return User::query()
            ->where("status", "active")
            ->orderBy("name")
            ->get(["id", "name"]);

    }

    private function branchQuery(): Builder {

        $query = Branch::query();

        $branchIds = $this->allowedBranchIds();

        if($branchIds !== null) {

            $query->whereIn("id", $branchIds);

        }

        return $query->orderBy("name");

    }

    public function allowedBranchIds(): ?array {

        return $this->allowedIds(AccessScopeService::BRANCH);

    }

    public function allowedCashRegisterIds(): ?array {

        return $this->allowedIds(AccessScopeService::CASH_REGISTER);

    }

    public function allowedWarehouseIds(): ?array {

        return $this->allowedIds(AccessScopeService::WAREHOUSE);

    }

    private function allowedIds(string $type): ?array {

        if(array_key_exists($type, $this->allowedIdsCache)) {

            return $this->allowedIdsCache[$type];

        }

        if(!$this->userId) {

            return $this->allowedIdsCache[$type] = null;

        }

        $user = $this->user();

        return $this->allowedIdsCache[$type] = $user
            ? AccessScopeService::allowedIds($user, $type)
            : [];

    }

    private function user(): ?User {

        if(!$this->userResolved) {

            $this->user = User::query()
                ->find($this->userId);

            $this->userResolved = true;

        }

        return $this->user;

    }

    private function customerQuery(): Builder {

        return Customer::query()
            ->orderBy("name");

    }
}
