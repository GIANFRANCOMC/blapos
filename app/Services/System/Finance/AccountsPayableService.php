<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Helpers\System\{Utilities};
use App\Models\System\Purchases\{PurchaseAccountPayable};
use App\Services\System\Base\{CompanyReferenceDataService};
use Carbon\{Carbon};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};

final class AccountsPayableService {
    public function paginate(int $userId, array $filters, int $perPage): LengthAwarePaginator {

        $paginator = $this->query($userId, $filters)
            ->with($this->listRelations())
            ->orderByRaw("CASE WHEN status IN ('paid', 'canceled') THEN 1 ELSE 0 END")
            ->orderBy("due_date")
            ->orderByDesc("id")
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn(PurchaseAccountPayable $account) => $this->formatAccount($account))
        );

        return $paginator;

    }

    public function find(int $userId, int $accountId): array {

        $account = $this->query($userId)
            ->with([
                ...$this->listRelations(),
                "payments" => fn($query) => $query
                    ->with(["paymentMethod:id,name,image_path", "paymentMethodVariant:id,name,image_path"])
                    ->latest("paid_at"),
            ])
            ->findOrFail($accountId);

        return $this->formatAccount($account, true);

    }

    public function summary(int $userId, array $filters = []): array {

        $query = $this->query($userId, $filters)
            ->where("status", "!=", "canceled");

        $amounts = (clone $query)
            ->selectRaw("currency_id, SUM(total_amount) total_amount, SUM(paid_amount) paid_amount, SUM(pending_amount) pending_amount")
            ->with("currency:id,code,sign")
            ->groupBy("currency_id")
            ->get()
            ->map(fn($row) => [
                "currency_id" => (int) $row->currency_id,
                "code" => $row->currency?->code ?? "",
                "sign" => $row->currency?->sign ?? "",
                "total" => Utilities::round((float) $row->total_amount),
                "paid" => Utilities::round((float) $row->paid_amount),
                "pending" => Utilities::round((float) $row->pending_amount),
            ])
            ->values();

        $overdueQuery = fn(Builder $installments) => $installments
            ->whereIn("status", ["pending", "partial", "overdue"])
            ->where("pending_amount", ">", 0)
            ->whereDate("due_date", "<", now()->toDateString());

        $overdueAmounts = (clone $query)
            ->whereHas("installments", $overdueQuery)
            ->selectRaw("currency_id, SUM(pending_amount) overdue_amount")
            ->groupBy("currency_id")
            ->pluck("overdue_amount", "currency_id");

        return [
            "accounts" => (clone $query)->count(),
            "overdue_accounts" => (clone $query)->whereHas("installments", $overdueQuery)->count(),
            "amounts" => $amounts->map(function(array $amount) use ($overdueAmounts) {

                $amount["overdue"] = Utilities::round(
                    (float) ($overdueAmounts[$amount["currency_id"]] ?? 0)
                );

                return $amount;

            })->values(),
        ];

    }

    private function query(int $userId, array $filters = []): Builder {

        $query = PurchaseAccountPayable::query();
        $warehouseIds = CompanyReferenceDataService::forUser($userId)->allowedWarehouseIds();

        if($warehouseIds !== null) {

            $query->whereHas("purchase", fn(Builder $purchase) => $purchase->whereIn("warehouse_id", $warehouseIds));

        }

        $search = trim((string) ($filters["search"] ?? ""));

        if($search !== "") {

            $query->where(function(Builder $searchQuery) use ($search) {

                $searchQuery
                    ->whereHas("supplier", fn(Builder $supplier) => $supplier
                        ->where("name", "like", "%{$search}%")
                        ->orWhere("document_number", "like", "%{$search}%"))
                    ->orWhereHas("purchase", fn(Builder $purchase) => $purchase
                        ->where("reference", "like", "%{$search}%")
                        ->orWhere("document_series", "like", "%{$search}%")
                        ->orWhere("document_number", "like", "%{$search}%"));

            });

        }

        $status = (string) ($filters["status"] ?? "");

        if($status === "overdue") {

            $query->whereHas("installments", fn(Builder $installments) => $installments
                ->whereIn("status", ["pending", "partial", "overdue"])
                ->where("pending_amount", ">", 0)
                ->whereDate("due_date", "<", now()->toDateString()));

        }elseif(in_array($status, ["pending", "partial", "paid", "canceled"], true)) {

            $query->where("status", $status);

        }

        return $query
            ->when($filters["date_from"] ?? null, fn(Builder $dateQuery, string $date) => $dateQuery->whereDate("issue_date", ">=", $date))
            ->when($filters["date_to"] ?? null, fn(Builder $dateQuery, string $date) => $dateQuery->whereDate("issue_date", "<=", $date));

    }

    private function listRelations(): array {

        return [
            "supplier:id,name,document_number",
            "currency:id,code,sign",
            "purchase:id,warehouse_id,document_type,document_series,document_number,reference,issue_date,total,paid_amount,balance_due,payment_status,status",
            "purchase.warehouse:id,branch_id,name",
            "purchase.warehouse.branch:id,name",
            "installments" => fn($query) => $query
                ->select(["id", "purchase_account_payable_id", "installment_number", "due_date", "amount", "paid_amount", "pending_amount", "status"])
                ->orderBy("installment_number"),
        ];

    }

    private function formatAccount(PurchaseAccountPayable $account, bool $includePayments = false): array {

        $installments = $account->installments->map(function($installment) {

            $overdue = (float) $installment->pending_amount > 0
                && $installment->due_date
                && Carbon::parse($installment->due_date)->isBefore(today());

            return [
                "id" => (int) $installment->id,
                "number" => (int) $installment->installment_number,
                "due_date" => $installment->due_date?->format("Y-m-d"),
                "amount" => (float) $installment->amount,
                "paid_amount" => (float) $installment->paid_amount,
                "pending_amount" => (float) $installment->pending_amount,
                "status" => $overdue ? "overdue" : $installment->status,
            ];

        });

        $status = $account->status;

        if(!in_array($status, ["paid", "canceled"], true)) {

            $status = $installments->contains(fn(array $installment) => $installment["status"] === "overdue")
                ? "overdue"
                : ((float) $account->paid_amount > 0 ? "partial" : "pending");

        }

        $purchase = $account->purchase;

        $document = trim(implode("-", array_filter([$purchase?->document_series, $purchase?->document_number])));
        $result = [
            "id" => (int) $account->id,
            "purchase_id" => (int) $account->purchase_header_id,
            "document" => $document !== "" ? $document : ($purchase?->reference ?? "Compra #{$account->purchase_header_id}"),
            "branch" => $purchase?->warehouse?->branch?->name,
            "warehouse" => $purchase?->warehouse?->name,
            "supplier" => [
                "id" => (int) $account->supplier_id,
                "name" => $account->supplier?->name ?? "Proveedor",
                "document_number" => $account->supplier?->document_number ?? "",
            ],
            "currency" => [
                "id" => (int) $account->currency_id,
                "code" => $account->currency?->code ?? "",
                "sign" => $account->currency?->sign ?? "",
            ],
            "issue_date" => $account->issue_date?->format("Y-m-d"),
            "due_date" => $account->due_date?->format("Y-m-d"),
            "total_amount" => (float) $account->total_amount,
            "paid_amount" => (float) $account->paid_amount,
            "pending_amount" => (float) $account->pending_amount,
            "status" => $status,
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
            ])->values();

        }

        return $result;

    }
}
