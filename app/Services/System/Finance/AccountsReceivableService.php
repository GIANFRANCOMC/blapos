<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Helpers\System\{Utilities};
use App\Models\System\Sales\{SaleAccountReceivable};
use App\Services\System\Base\{CompanyReferenceDataService};
use Carbon\{Carbon};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};

final class AccountsReceivableService {
    public function paginate(int $companyId, int $userId, array $filters, int $perPage): LengthAwarePaginator {

        $paginator = $this->query($companyId, $userId, $filters)
            ->with($this->listRelations())
            ->orderByRaw("CASE WHEN status IN ('paid', 'canceled') THEN 1 ELSE 0 END")
            ->orderBy("due_date")
            ->orderByDesc("id")
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn(SaleAccountReceivable $account) => $this->formatAccount($account))
        );

        return $paginator;

    }

    public function find(int $companyId, int $userId, int $accountId): array {

        $account = $this->query($companyId, $userId)
            ->with([
                ...$this->listRelations(),
                "payments" => fn($query) => $query
                    ->with(["paymentMethod:id,name,image_path", "paymentMethodVariant:id,name,image_path"])
                    ->latest("paid_at"),
            ])
            ->findOrFail($accountId);

        return $this->formatAccount($account, true);

    }

    public function summary(int $companyId, int $userId, array $filters = []): array {

        $query = $this->query($companyId, $userId, $filters)
            ->where("status", "!=", "canceled");

        $amounts = (clone $query)
            ->selectRaw("currency_id, SUM(total_amount) as total_amount, SUM(paid_amount) as paid_amount, SUM(pending_amount) as pending_amount")
            ->with("currency:id,code,sign")
            ->groupBy("currency_id")
            ->get()
            ->map(function($row) use ($companyId) {

                return [
                    "currency_id" => (int) $row->currency_id,
                    "code" => $row->currency?->code ?? "",
                    "sign" => $row->currency?->sign ?? "",
                    "total" => Utilities::round((float) $row->total_amount, null, $companyId),
                    "paid" => Utilities::round((float) $row->paid_amount, null, $companyId),
                    "pending" => Utilities::round((float) $row->pending_amount, null, $companyId),
                ];

            })
            ->values();

        $overdueAmounts = (clone $query)
            ->whereHas("installments", fn($installmentQuery) => $installmentQuery
                ->whereIn("status", ["pending", "partial", "overdue"])
                ->where("pending_amount", ">", 0)
                ->whereDate("due_date", "<", now()->toDateString()))
            ->selectRaw("currency_id, SUM(pending_amount) as overdue_amount")
            ->groupBy("currency_id")
            ->pluck("overdue_amount", "currency_id");

        return [
            "accounts" => (clone $query)->count(),
            "overdue_accounts" => (clone $query)
                ->whereHas("installments", fn($installmentQuery) => $installmentQuery
                    ->whereIn("status", ["pending", "partial", "overdue"])
                    ->where("pending_amount", ">", 0)
                    ->whereDate("due_date", "<", now()->toDateString()))
                ->count(),
            "amounts" => $amounts->map(function($amount) use ($overdueAmounts, $companyId) {

                $amount["overdue"] = Utilities::round(
                    (float) ($overdueAmounts[$amount["currency_id"]] ?? 0),
                    null,
                    $companyId
                );

                return $amount;

            })->values(),
        ];

    }

    private function query(int $companyId, int $userId, array $filters = []): Builder {

        $query = SaleAccountReceivable::query();
        $branchIds = CompanyReferenceDataService::forUser($userId)->allowedBranchIds();

        if($branchIds !== null) {

            $query->whereHas("sale.serie", fn($serieQuery) => $serieQuery->whereIn("branch_id", $branchIds));

        }

        $search = trim((string) ($filters["search"] ?? ""));

        if($search !== "") {

            $query->where(function($searchQuery) use ($search) {

                $searchQuery
                    ->whereHas("customer", fn($customerQuery) => $customerQuery
                        ->where("name", "like", "%{$search}%")
                        ->orWhere("document_number", "like", "%{$search}%"))
                    ->orWhereHas("sale", fn($saleQuery) => $saleQuery
                        ->where("sequential", "like", "%{$search}%")
                        ->orWhereHas("serie", fn($serieQuery) => $serieQuery
                            ->whereRaw("CONCAT(code, number) LIKE ?", ["%{$search}%"])));

            });

        }

        $status = (string) ($filters["status"] ?? "");

        if($status === "overdue") {

            $query->whereHas("installments", fn($installmentQuery) => $installmentQuery
                ->whereIn("status", ["pending", "partial", "overdue"])
                ->where("pending_amount", ">", 0)
                ->whereDate("due_date", "<", now()->toDateString()));

        }elseif(in_array($status, ["pending", "partial", "paid", "canceled"], true)) {

            $query->where("status", $status);

        }

        if(!empty($filters["date_from"])) {

            $query->whereDate("issue_date", ">=", $filters["date_from"]);

        }

        if(!empty($filters["date_to"])) {

            $query->whereDate("issue_date", "<=", $filters["date_to"]);

        }

        return $query;

    }

    private function listRelations(): array {

        return [
            "customer:id,name,document_number",
            "currency:id,code,sign",
            "sale:id,serie_id,sequential,issue_date,total,paid_amount,balance_due,payment_status,status",
            "sale.serie:id,branch_id,code,number",
            "sale.serie.branch:id,name",
            "installments" => fn($query) => $query
                ->select(["id", "sale_account_receivable_id", "installment_number", "due_date", "amount", "paid_amount", "pending_amount", "status"])
                ->orderBy("installment_number"),
        ];

    }

    private function formatAccount(SaleAccountReceivable $account, bool $includePayments = false): array {

        $installments = $account->installments->map(function($installment) {

            $isOverdue = (float) $installment->pending_amount > 0
                && $installment->due_date
                && Carbon::parse($installment->due_date)->isBefore(today());

            return [
                "id" => (int) $installment->id,
                "number" => (int) $installment->installment_number,
                "due_date" => $installment->due_date?->format("Y-m-d"),
                "amount" => (float) $installment->amount,
                "paid_amount" => (float) $installment->paid_amount,
                "pending_amount" => (float) $installment->pending_amount,
                "status" => $isOverdue ? "overdue" : $installment->status,
            ];

        });

        $nextInstallment = $installments
            ->filter(fn($installment) => $installment["pending_amount"] > 0)
            ->sortBy(fn($installment) => $installment["due_date"] ?? "9999-12-31")
            ->first();

        $isOverdue = $installments->contains(fn($installment) => $installment["status"] === "overdue");
        $status = $account->status;

        if(!in_array($status, ["paid", "canceled"], true)) {

            $status = $isOverdue ? "overdue" : ((float) $account->paid_amount > 0 ? "partial" : "pending");

        }

        $result = [
            "id" => (int) $account->id,
            "sale_id" => (int) $account->sale_header_id,
            "document" => sprintf(
                "%s-%08d",
                $account->sale?->serie?->legible_serie ?? "VENTA",
                (int) ($account->sale?->sequential ?? 0)
            ),
            "branch" => $account->sale?->serie?->branch?->name,
            "customer" => [
                "id" => (int) $account->customer_id,
                "name" => $account->customer?->name ?? "Cliente",
                "document_number" => $account->customer?->document_number ?? "",
            ],
            "currency" => [
                "id" => (int) $account->currency_id,
                "code" => $account->currency?->code ?? "",
                "sign" => $account->currency?->sign ?? "",
            ],
            "issue_date" => $account->issue_date?->format("Y-m-d"),
            "due_date" => $account->due_date?->format("Y-m-d"),
            "payment_modality" => $account->payment_modality,
            "original_amount" => (float) $account->original_amount,
            "extra_percentage" => (float) $account->extra_percentage,
            "extra_amount" => (float) $account->extra_amount,
            "total_amount" => (float) $account->total_amount,
            "paid_amount" => (float) $account->paid_amount,
            "pending_amount" => (float) $account->pending_amount,
            "status" => $status,
            "observation" => $account->observation,
            "next_installment" => $nextInstallment,
            "installments" => $installments->values(),
        ];

        if($includePayments) {

            $result["payments"] = $account->payments->map(fn($payment) => [
                "id" => (int) $payment->id,
                "paid_at" => $payment->paid_at?->format("Y-m-d H:i:s"),
                "amount" => (float) $payment->amount,
                "method" => $payment->paymentMethodVariant?->name ?? $payment->paymentMethod?->name ?? "Pago",
                "image_path" => $payment->paymentMethodVariant?->image_path ?? $payment->paymentMethod?->image_path,
                "reference" => $payment->reference,
                "observation" => $payment->observation,
            ])->values();

        }

        return $result;

    }
}
