<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Helpers\System\{Utilities};
use App\Models\System\Finance\{CashMovement, CashSession, MiscExpense};
use App\Services\System\Organizations\{AccessScopeService};
use DomainException;
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

final class MiscExpenseService {
    public static function query(int $companyId, array $filters = [], ?int $userId = null): Builder {

        $query = MiscExpense::query()
            ->with([
                "branch:id,name",
                "cashSession:id,cash_register_id,status",
                "paymentMethod:id,name",
                "currency:id,code,sign",
                "category:id,name",
                "responsibleUser:id,name",
            ]);

        if($userId !== null) {

            $branchIds = \App\Services\System\Base\CompanyReferenceDataService::for($companyId, $userId)->allowedBranchIds();

            if($branchIds !== null) {

                $query->where(function($query) use ($branchIds) {

                    $query->whereNull("branch_id")->orWhereIn("branch_id", $branchIds);

                });

            }

        }

        $word = trim((string) ($filters["word"] ?? ""));

        if($word !== "") {

            $query->where(function($query) use ($word) {

                $query->where("concept", "like", "%{$word}%")
                    ->orWhere("reference", "like", "%{$word}%")
                    ->orWhere("description", "like", "%{$word}%")
                    ->orWhere("observation", "like", "%{$word}%");

            });

        }

        if(!empty($filters["status"])) {

            $query->where("status", $filters["status"]);

        }

        if(!empty($filters["branch_id"])) {

            $query->where("branch_id", (int) $filters["branch_id"]);

        }

        return $query->orderByDesc("expense_date")->orderByDesc("id");

    }

    public static function create(int $companyId, int $userId, array $data): MiscExpense {

        return DB::transaction(function() use ($companyId, $userId, $data) {

            $branchId = isset($data["branch_id"]) ? (int) $data["branch_id"] : null;

            if($branchId && !AccessScopeService::canAccess(auth()->user(), AccessScopeService::BRANCH, $branchId)) {

                throw new DomainException("No tienes acceso a la sucursal seleccionada.");

            }

            $cashSessionId = isset($data["cash_session_id"]) ? (int) $data["cash_session_id"] : null;

            $cashSession = null;

            if($cashSessionId) {

                $cashSession = CashSession::query()
                    ->where("status", "open")
                    ->findOrFail($cashSessionId);

            }

            $amount = Utilities::round($data["amount"]);

            if($amount <= 0) {

                throw new DomainException("El gasto debe tener un importe mayor que cero.");

            }

            $expense = MiscExpense::create([
                "branch_id" => $branchId ?? $cashSession?->branch_id,
                "cash_session_id" => $cashSession?->id,
                "payment_method_id" => $data["payment_method_id"] ?? null,
                "currency_id" => (int) $data["currency_id"],
                "misc_expense_category_id" => $data["misc_expense_category_id"] ?? null,
                "responsible_user_id" => $data["responsible_user_id"] ?? null,
                "expense_date" => $data["expense_date"],
                "reference" => $data["reference"] ?? null,
                "concept" => trim((string) $data["concept"]),
                "amount" => $amount,
                "description" => $data["description"] ?? null,
                "observation" => $data["observation"] ?? null,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            if($cashSession) {

                CashMovement::create([
                    "branch_id" => $expense->branch_id,
                    "cash_session_id" => $cashSession->id,
                    "payment_method_id" => $expense->payment_method_id,
                    "user_id" => $userId,
                    "movement_type" => "expense",
                    "origin_type" => "misc_expense",
                    "origin_id" => $expense->id,
                    "amount" => $amount * -1,
                    "reference" => $expense->concept,
                    "note" => $expense->observation,
                    "occurred_at" => now(),
                    "status" => "active",
                    "created_by" => $userId,
                ]);

            }

            return $expense->load(["branch", "paymentMethod", "currency", "category", "responsibleUser"]);

        });

    }

    public static function cancel(int $companyId, int $expenseId, int $userId): MiscExpense {

        return DB::transaction(function() use ($companyId, $expenseId, $userId) {

            $expense = MiscExpense::query()
                ->whereKey($expenseId)
                ->lockForUpdate()
                ->firstOrFail();

            if($expense->status !== "active") {

                throw new DomainException("El gasto ya fue anulado.");

            }

            $expense->update([
                "status" => "canceled",
                "canceled_at" => now(),
                "canceled_by" => $userId,
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);

            CashMovement::query()
                ->where("origin_type", "misc_expense")
                ->where("origin_id", $expense->id)
                ->update([
                    "status" => "canceled",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ]);

            return $expense->refresh();

        });

    }
}
