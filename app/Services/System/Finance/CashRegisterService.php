<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Helpers\System\{Utilities};
use App\Models\System\Finance\{CashMovement, CashRegister, CashSession, CashSessionInventoryCount, CashSessionPayment, PaymentMethod};
use App\Models\System\Organizations\{Branch};
use App\Models\System\Warehouses\{WarehouseItem};
use App\Services\System\Base\{CompanyReferenceDataService};
use App\Services\System\Sales\{SaleConfigService};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use Carbon\{Carbon};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};
use RuntimeException;

final class CashRegisterService {
    public function listRegisters(int $companyId, ?int $userId = null) {

        $query = CashRegister::query()
            ->with(["branch", "openSession.paymentSummary.paymentMethod"]);

        $cashRegisterIds = $userId !== null
            ? CompanyReferenceDataService::for($companyId, $userId)->allowedCashRegisterIds()
            : null;

        if($cashRegisterIds !== null) {

            $query->whereIn("id", $cashRegisterIds);

        }

        return $query->orderBy("name")
            ->get()
            ->map(fn(CashRegister $register) => $this->formatRegister($register));

    }

    public function createRegister(int $companyId, int $userId, array $data): CashRegister {

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $branch = Branch::query()
                ->where("status", "active")
                ->find((int) $data["branch_id"]);

            if(!$branch) {

                throw new RuntimeException("Seleccione una sucursal activa para registrar la caja.");

            }

            $allowedBranchIds = CompanyReferenceDataService::for($companyId, $userId)->allowedBranchIds();

            if($allowedBranchIds !== null && !in_array((int) $branch->id, $allowedBranchIds, true)) {

                throw new RuntimeException("No tienes acceso a la sucursal seleccionada.");

            }

            if((bool) ($data["is_main"] ?? false)) {

                CashRegister::query()
                    ->where("branch_id", $branch->id)
                    ->update(["is_main" => false]);

            }

            $register = CashRegister::create([
                "branch_id" => $branch->id,
                "code" => $data["code"] ?? $this->generateRegisterCode($companyId),
                "name" => $data["name"],
                "is_main" => (bool) ($data["is_main"] ?? false),
                "status" => $data["status"] ?? "active",
                "created_by" => $userId,
            ]);

            $this->clearOperationalCaches($companyId);

            return $register->load("branch");

        });

    }

    public function listSessions(int $companyId, array $filters, int $perPage, ?int $userId = null): LengthAwarePaginator {

        return $this->sessionsQuery($companyId, $filters, $userId)
            ->latest("opened_at")
            ->paginate($perPage);

    }

    public function listMovements(int $companyId, array $filters, int $perPage, ?int $userId = null): LengthAwarePaginator {

        $query = CashMovement::query()
            ->with(["branch", "cashSession.register", "paymentMethod", "user"])
            ->when($filters["branch_id"] ?? null, fn($query, $branchId) => $query->where("branch_id", $branchId))
            ->when($filters["cash_session_id"] ?? null, fn($query, $sessionId) => $query->where("cash_session_id", $sessionId))
            ->when($filters["payment_method_id"] ?? null, fn($query, $paymentMethodId) => $query->where("payment_method_id", $paymentMethodId))
            ->when($filters["user_id"] ?? null, fn($query, $responsibleId) => $query->where("user_id", $responsibleId))
            ->when($filters["movement_type"] ?? null, fn($query, $type) => $query->where("movement_type", $type))
            ->when($filters["cash_register_id"] ?? null, function($query, $registerId) {

                $query->whereHas("cashSession", fn($sessionQuery) => $sessionQuery->where("cash_register_id", $registerId));

            })
            ->when($filters["search"] ?? null, function($query, $search) {

                $query->where(function($subQuery) use ($search) {

                    $subQuery->where("reference", "like", "%{$search}%")
                        ->orWhere("note", "like", "%{$search}%")
                        ->orWhereHas("cashSession.register", function($registerQuery) use ($search) {

                            $registerQuery->where("name", "like", "%{$search}%")
                                ->orWhere("code", "like", "%{$search}%");

                        })
                        ->orWhereHas("user", fn($userQuery) => $userQuery->where("name", "like", "%{$search}%"));

                });

            })
            ->when($filters["date_from"] ?? null, fn($query, $date) => $query->where("occurred_at", ">=", Utilities::startOfDay($date)))
            ->when($filters["date_to"] ?? null, fn($query, $date) => $query->where("occurred_at", "<=", Utilities::endOfDay($date)))
            ->where("status", "active")
            ->latest("occurred_at");

        $cashRegisterIds = $this->allowedCashRegisterIds($companyId, $userId);

        if($cashRegisterIds !== null) {

            $query->whereHas("cashSession", fn($sessionQuery) => $sessionQuery->whereIn("cash_register_id", $cashRegisterIds));

        }

        return $query->paginate($perPage);

    }

    public function summary(int $companyId, array $filters, ?int $userId = null): array {

        $sessions = $this->sessionsQuery($companyId, $filters, $userId)->get();
        $sessionIds = $sessions->pluck("id")->all();

        if(empty($sessionIds)) {

            return [
                "sessions" => [],
                "payments" => [],
                "totals" => $this->emptyTotals(),
            ];

        }

        $payments = CashMovement::query()
            ->select([
                "payment_method_id",
                DB::raw("SUM(amount) as amount"),
            ])
            ->with("paymentMethod")
            ->whereIn("cash_session_id", $sessionIds)
            ->where("status", "active")
            ->when($filters["payment_method_id"] ?? null, fn($query, $paymentMethodId) => $query->where("payment_method_id", $paymentMethodId))
            ->groupBy("payment_method_id")
            ->get()
            ->map(function($row) use ($companyId) {

                return [
                    "payment_method_id" => $row->payment_method_id,
                    "payment_method" => $row->paymentMethod,
                    "amount" => Utilities::round((float) $row->amount, null, $companyId),
                ];

            })
            ->values();

        $paymentMethodId = $filters["payment_method_id"] ?? null;
        $expected = $paymentMethodId
            ? Utilities::round((float) CashMovement::query()
                ->whereIn("cash_session_id", $sessionIds)
                ->where("payment_method_id", $paymentMethodId)
                ->where("status", "active")
                ->sum("amount"), null, $companyId)
            : Utilities::round((float) $sessions->sum("expected_amount"), null, $companyId);

        $counted = $paymentMethodId
            ? Utilities::round((float) CashSessionPayment::query()
                ->whereIn("cash_session_id", $sessionIds)
                ->where("payment_method_id", $paymentMethodId)
                ->sum("counted_amount"), null, $companyId)
            : Utilities::round((float) $sessions->sum("counted_amount"), null, $companyId);

        return [
            "sessions" => $sessions,
            "payments" => $payments,
            "totals" => [
                "opening" => $paymentMethodId ? 0 : Utilities::round((float) $sessions->sum("opening_amount"), null, $companyId),
                "expected" => $expected,
                "counted" => $counted,
                "difference" => Utilities::round($counted - $expected, null, $companyId),
            ],
        ];

    }

    public function openSession(int $companyId, int $userId, array $data): CashSession {

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $register = CashRegister::query()
                ->with("branch")
                ->where("status", "active")
                ->findOrFail((int) $data["cash_register_id"]);

            $this->assertRegisterAccess($companyId, $userId, (int) $register->id);

            $hasOpenSession = CashSession::query()
                ->where("cash_register_id", $register->id)
                ->where("status", "open")
                ->exists();

            if($hasOpenSession) {

                throw new RuntimeException("Esta caja ya tiene una apertura activa.");

            }

            $openingAmount = Utilities::round((float) ($data["opening_amount"] ?? 0), null, $companyId);

            $session = CashSession::create([
                "branch_id" => $register->branch_id,
                "cash_register_id" => $register->id,
                "opened_by" => $userId,
                "opened_at" => Carbon::now(),
                "opening_amount" => $openingAmount,
                "expected_amount" => $openingAmount,
                "counted_amount" => 0,
                "difference_amount" => 0,
                "observation" => $data["observation"] ?? null,
                "status" => "open",
                "created_by" => $userId,
            ]);

            CashMovement::create([
                "branch_id" => $register->branch_id,
                "cash_session_id" => $session->id,
                "user_id" => $userId,
                "movement_type" => "opening",
                "origin_type" => "cash_session",
                "origin_id" => $session->id,
                "amount" => $openingAmount,
                "reference" => "Apertura de caja",
                "note" => $data["observation"] ?? null,
                "occurred_at" => Carbon::now(),
                "status" => "active",
                "created_by" => $userId,
            ]);

            $this->clearOperationalCaches($companyId);

            return $session->load(["register", "branch", "openedBy"]);

        });

    }

    public function closeSession(int $companyId, int $userId, array $data): CashSession {

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $session = CashSession::query()
                ->with(["register", "branch"])
                ->where("status", "open")
                ->findOrFail((int) $data["cash_session_id"]);

            $this->assertRegisterAccess($companyId, $userId, (int) $session->cash_register_id);

            $inventoryCounts = is_array($data["inventory_counts"] ?? null) ? $data["inventory_counts"] : [];

            if($session->register?->is_main) {

                $hasOpenSecondarySessions = CashSession::query()
                    ->where("branch_id", $session->branch_id)
                    ->where("status", "open")
                    ->where("id", "!=", $session->id)
                    ->whereHas("register", fn($query) => $query->where("is_main", false))
                    ->exists();

                if($hasOpenSecondarySessions) {

                    throw new RuntimeException("Primero cierra las cajas secundarias de la sucursal antes de cerrar la caja principal.");

                }

                if(empty($inventoryCounts) && $this->branchHasCountableInventory($companyId, (int) $session->branch_id)) {

                    throw new RuntimeException("Completa el conteo físico de inventario antes de cerrar la caja principal.");

                }

            }

            $expectedAmount = Utilities::round((float) CashMovement::query()
                ->where("cash_session_id", $session->id)
                ->where("status", "active")
                ->sum("amount"), null, $companyId);

            $countedPayments = collect($data["payments"] ?? [])
                ->map(function($payment) use ($companyId) {

                    return [
                        "payment_method_id" => $payment["payment_method_id"] ?? null,
                        "counted_amount" => Utilities::round((float) ($payment["counted_amount"] ?? 0), null, $companyId),
                    ];

                });

            $countedAmount = $countedPayments->isNotEmpty()
                ? Utilities::round((float) $countedPayments->sum("counted_amount"), null, $companyId)
                : Utilities::round((float) ($data["counted_amount"] ?? 0), null, $companyId);

            $session->update([
                "closed_by" => $userId,
                "closed_at" => Carbon::now(),
                "expected_amount" => $expectedAmount,
                "counted_amount" => $countedAmount,
                "difference_amount" => Utilities::round($countedAmount - $expectedAmount, null, $companyId),
                "observation" => $data["observation"] ?? $session->observation,
                "status" => "closed",
                "updated_by" => $userId,
            ]);

            CashSessionPayment::query()->where("cash_session_id", $session->id)->delete();

            foreach($countedPayments as $payment) {

                $expectedByMethod = $this->expectedByPaymentMethod($companyId, $session->id, $payment["payment_method_id"]);
                $paymentMethod = $payment["payment_method_id"]
                    ? PaymentMethod::query()->find($payment["payment_method_id"])
                    : null;

                CashSessionPayment::create([
                    "cash_session_id" => $session->id,
                    "payment_method_id" => $payment["payment_method_id"],
                    "payment_method_name" => $paymentMethod?->name ?? "Efectivo / apertura",
                    "expected_amount" => $expectedByMethod,
                    "counted_amount" => $payment["counted_amount"],
                    "difference_amount" => Utilities::round($payment["counted_amount"] - $expectedByMethod, null, $companyId),
                    "created_by" => $userId,
                ]);

            }

            $this->syncInventoryCounts(
                $companyId,
                $userId,
                $session,
                $inventoryCounts
            );

            CashMovement::create([
                "branch_id" => $session->branch_id,
                "cash_session_id" => $session->id,
                "user_id" => $userId,
                "movement_type" => "closing",
                "origin_type" => "cash_session",
                "origin_id" => $session->id,
                "amount" => 0,
                "reference" => "Cierre de caja",
                "note" => $data["observation"] ?? null,
                "occurred_at" => Carbon::now(),
                "status" => "active",
                "created_by" => $userId,
            ]);

            $this->clearOperationalCaches($companyId);

            return $session->load(["register", "branch", "closedBy", "paymentSummary.paymentMethod", "inventoryCounts.item", "inventoryCounts.warehouse"]);

        });

    }

    public function registerMovement(int $companyId, int $userId, array $data): CashMovement {

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $session = CashSession::query()
                ->with("register")
                ->where("status", "open")
                ->findOrFail((int) $data["cash_session_id"]);

            $this->assertRegisterAccess($companyId, $userId, (int) $session->cash_register_id);

            $movementType = (string) $data["movement_type"];
            $amount = Utilities::round((float) $data["amount"], null, $companyId);

            if(in_array($movementType, ["expense"], true)) {

                $amount = abs($amount) * -1;

            }else {

                $amount = abs($amount);

            }

            return CashMovement::create([
                "branch_id" => $session->branch_id,
                "cash_session_id" => $session->id,
                "payment_method_id" => $data["payment_method_id"] ?? null,
                "user_id" => $userId,
                "movement_type" => $movementType,
                "origin_type" => "cash_manual",
                "origin_id" => null,
                "amount" => $amount,
                "reference" => $data["reference"] ?? $this->manualMovementLabel($movementType),
                "note" => $data["note"] ?? null,
                "occurred_at" => Carbon::now(),
                "status" => "active",
                "created_by" => $userId,
            ])->load(["branch", "cashSession.register", "paymentMethod", "user"]);

        });

    }

    public function movementsForExport(int $companyId, array $filters, ?int $userId = null) {

        $query = CashMovement::query()
            ->with(["branch", "cashSession.register", "paymentMethod", "user"])
            ->when($filters["branch_id"] ?? null, fn($query, $branchId) => $query->where("branch_id", $branchId))
            ->when($filters["cash_register_id"] ?? null, function($query, $registerId) {

                $query->whereHas("cashSession", fn($sessionQuery) => $sessionQuery->where("cash_register_id", $registerId));

            })
            ->when($filters["payment_method_id"] ?? null, fn($query, $paymentMethodId) => $query->where("payment_method_id", $paymentMethodId))
            ->when($filters["user_id"] ?? null, fn($query, $responsibleId) => $query->where("user_id", $responsibleId))
            ->when($filters["date_from"] ?? null, fn($query, $date) => $query->where("occurred_at", ">=", Utilities::startOfDay($date)))
            ->when($filters["date_to"] ?? null, fn($query, $date) => $query->where("occurred_at", "<=", Utilities::endOfDay($date)))
            ->where("status", "active")
            ->latest("occurred_at");

        $cashRegisterIds = $this->allowedCashRegisterIds($companyId, $userId);

        if($cashRegisterIds !== null) {

            $query->whereHas("cashSession", fn($sessionQuery) => $sessionQuery->whereIn("cash_register_id", $cashRegisterIds));

        }

        return $query->get();

    }

    private function sessionsQuery(int $companyId, array $filters, ?int $userId = null): Builder {

        $query = CashSession::query()
            ->with(["register", "branch", "openedBy", "closedBy", "paymentSummary.paymentMethod"])
            ->when($filters["branch_id"] ?? null, fn($query, $branchId) => $query->where("branch_id", $branchId))
            ->when($filters["cash_register_id"] ?? null, fn($query, $registerId) => $query->where("cash_register_id", $registerId))
            ->when($filters["user_id"] ?? null, function($query, $responsibleId) {

                $query->where(function($userQuery) use ($responsibleId) {

                    $userQuery->where("opened_by", $responsibleId)
                        ->orWhere("closed_by", $responsibleId);

                });

            })
            ->when($filters["payment_method_id"] ?? null, function($query, $paymentMethodId) {

                $query->where(function($paymentQuery) use ($paymentMethodId) {

                    $paymentQuery->whereHas("paymentSummary", fn($summaryQuery) => $summaryQuery
                        ->where("payment_method_id", $paymentMethodId))
                        ->orWhereHas("movements", fn($movementQuery) => $movementQuery
                            ->where("payment_method_id", $paymentMethodId)
                            ->where("status", "active"));

                });

            })
            ->when($filters["status"] ?? null, fn($query, $status) => $query->where("status", $status))
            ->when($filters["search"] ?? null, function($query, $search) {

                $query->where(function($subQuery) use ($search) {

                    $subQuery->where("observation", "like", "%{$search}%")
                        ->orWhereHas("register", function($registerQuery) use ($search) {

                            $registerQuery->where("name", "like", "%{$search}%")
                                ->orWhere("code", "like", "%{$search}%");

                        })
                        ->orWhereHas("openedBy", fn($userQuery) => $userQuery->where("name", "like", "%{$search}%"))
                        ->orWhereHas("closedBy", fn($userQuery) => $userQuery->where("name", "like", "%{$search}%"));

                });

            })
            ->when($filters["date_from"] ?? null, fn($query, $date) => $query->where("opened_at", ">=", Utilities::startOfDay($date)))
            ->when($filters["date_to"] ?? null, fn($query, $date) => $query->where("opened_at", "<=", Utilities::endOfDay($date)));

        $cashRegisterIds = $this->allowedCashRegisterIds($companyId, $userId);

        if($cashRegisterIds !== null) {

            $query->whereIn("cash_register_id", $cashRegisterIds);

        }

        return $query;

    }

    private function allowedCashRegisterIds(int $companyId, ?int $userId): ?array {

        return $userId === null
            ? null
            : CompanyReferenceDataService::for($companyId, $userId)->allowedCashRegisterIds();

    }

    private function formatRegister(CashRegister $register): array {

        $openSession = $register->openSession;

        return [
            "id" => $register->id,
            "code" => $register->code,
            "name" => $register->name,
            "is_main" => (bool) $register->is_main,
            "status" => $register->status,
            "branch" => $register->branch,
            "open_session" => $openSession,
            "is_open" => $openSession !== null,
            "current_amount" => $openSession ? Utilities::round((float) $openSession->expected_amount) : 0,
        ];

    }

    private function expectedByPaymentMethod(int $companyId, int $sessionId, ?int $paymentMethodId): float {

        return Utilities::round((float) CashMovement::query()
            ->where("cash_session_id", $sessionId)
            ->where("status", "active")
            ->when($paymentMethodId === null, fn($query) => $query->whereNull("payment_method_id"))
            ->when($paymentMethodId !== null, fn($query) => $query->where("payment_method_id", $paymentMethodId))
            ->sum("amount"), null, $companyId);

    }

    private function syncInventoryCounts(int $companyId, int $userId, CashSession $session, array $counts): void {

        if(!$session->register?->is_main || empty($counts)) {

            return;

        }

        CashSessionInventoryCount::query()
            ->where("cash_session_id", $session->id)
            ->delete();

        foreach($counts as $count) {

            $warehouseId = (int) ($count["warehouse_id"] ?? 0);
            $itemId = (int) ($count["item_id"] ?? 0);

            if($warehouseId <= 0 || $itemId <= 0) {

                continue;

            }

            $warehouseItem = WarehouseItem::query()
                ->whereHas("warehouse", function($query) use ($session) {

                    $query->where("branch_id", $session->branch_id);

                })
                ->where("warehouse_id", $warehouseId)
                ->where("item_id", $itemId)
                ->first();

            $systemQuantity = Utilities::round((float) ($warehouseItem?->quantity ?? 0), null, $companyId);
            $countedQuantity = Utilities::round((float) ($count["counted_quantity"] ?? $systemQuantity), null, $companyId);
            $difference = Utilities::round($countedQuantity - $systemQuantity, null, $companyId);
            $movement = null;

            if(abs($difference) >= 0.00001) {

                $movement = InventoryMovementService::apply([
                    "warehouse_id" => $warehouseId,
                    "item_id" => $itemId,
                    "user_id" => $userId,
                    "movement_type" => InventoryMovementService::TYPE_CORRECTION,
                    "origin_type" => InventoryMovementService::ORIGIN_PHYSICAL_COUNT,
                    "origin_id" => $session->id,
                    "resulting_balance" => $countedQuantity,
                    "reason" => "Ajuste por conteo físico en cierre de caja principal.",
                    "metadata" => [
                        "cash_session_id" => $session->id,
                        "cash_register_id" => $session->cash_register_id,
                        "observation" => $count["observation"] ?? null,
                    ],
                    "allow_negative" => false,
                ]);

            }

            CashSessionInventoryCount::create([
                "branch_id" => $session->branch_id,
                "cash_session_id" => $session->id,
                "warehouse_id" => $warehouseId,
                "item_id" => $itemId,
                "inventory_movement_id" => $movement?->id,
                "system_quantity" => $systemQuantity,
                "counted_quantity" => $countedQuantity,
                "difference_quantity" => $difference,
                "observation" => $count["observation"] ?? null,
                "status" => $movement ? "adjusted" : "ignored",
                "created_by" => $userId,
            ]);

        }

    }

    private function branchHasCountableInventory(int $companyId, int $branchId): bool {

        return WarehouseItem::query()
            ->where("status", "active")
            ->whereHas("warehouse", function($query) use ($branchId) {

                $query->where("branch_id", $branchId)
                    ->where("status", "active");

            })
            ->whereHas("item", function($query) {

                $query->where("type", "product")
                    ->where("status", "active");

            })
            ->exists();

    }

    private function emptyTotals(): array {

        return [
            "opening" => 0,
            "expected" => 0,
            "counted" => 0,
            "difference" => 0,
        ];

    }

    private function manualMovementLabel(string $movementType): string {

        return match ($movementType) {
            "income" => "Ingreso manual de caja",
            "expense" => "Salida manual de caja",
            "adjustment" => "Ajuste manual de caja",
            default => "Movimiento manual de caja"
        };

    }

    private function generateRegisterCode(int $companyId): string {

        do {

            $code = "CAJ-".strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

        } while(CashRegister::query()->where("code", $code)->exists());

        return $code;

    }

    private function assertRegisterAccess(int $companyId, int $userId, int $cashRegisterId): void {

        $allowedIds = $this->allowedCashRegisterIds($companyId, $userId);

        if($allowedIds !== null && !in_array($cashRegisterId, $allowedIds, true)) {

            throw new RuntimeException("No tienes acceso a la caja seleccionada.");

        }

    }

    private function clearOperationalCaches(int $companyId): void {

        CashRegisterConfigService::clearAllCache($companyId);
        SaleConfigService::clearAllCache($companyId);

    }
}
