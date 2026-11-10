<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Helpers\System\{Numbers};
use App\Models\System\Purchases\{PurchaseAccountPayable, PurchaseHeader, PurchasePayableInstallment, PurchasePayablePayment};
use App\Models\System\Sales\{SaleAccountReceivable, SaleHeader, SaleReceivableInstallment, SaleReceivablePayment};
use Carbon\{Carbon};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\{Collection};

final class CommercialCreditAccountService {

    public const PAID_NOW = "paid_now";

    public const CASH_ON_DELIVERY = "cash_on_delivery";

    public const INSTALLMENTS = "installments";

    public static function overdueInstallments(Builder $installments): Builder {

        return $installments
            ->whereIn("status", ["pending", "partial", "overdue"])
            ->whereRaw(Numbers::sqlRound("pending_amount")." > 0")
            ->where("due_date", "<", now()->toDateString());

    }

    public static function summarize(Builder $accounts): array {

        $accounts = (clone $accounts)->where("status", "!=", "canceled");
        $model = $accounts->getModel();
        $relation = $model->installments();
        $foreignKey = $relation->getForeignKeyName();
        $installments = self::overdueInstallments($relation->getRelated()->newQuery())
            ->select($foreignKey)
            ->selectRaw("SUM(pending_amount) AS overdue_amount")
            ->groupBy($foreignKey);

        $groups = $accounts
            ->leftJoinSub($installments, "overdue_installments", "overdue_installments.".$foreignKey, "=", $model->qualifyColumn("id"))
            ->selectRaw("currency_id, COUNT(*) AS accounts, COUNT(overdue_installments.overdue_amount) AS overdue_accounts")
            ->selectRaw("SUM(total_amount) AS total_amount, SUM(paid_amount) AS paid_amount, SUM(pending_amount) AS pending_amount")
            ->selectRaw("SUM(overdue_installments.overdue_amount) AS overdue_amount")
            ->with("currency:id,code,sign")
            ->groupBy("currency_id")
            ->get();

        return [
            "accounts" => $groups->sum("accounts"),
            "overdue_accounts" => $groups->sum("overdue_accounts"),
            "amounts" => $groups->map(fn($row) => [
                "currency_id" => (int) $row->currency_id,
                "code" => $row->currency?->code ?? "",
                "sign" => $row->currency?->sign ?? "",
                "total" => Numbers::storage($row->getRawOriginal("total_amount")),
                "paid" => Numbers::storage($row->getRawOriginal("paid_amount")),
                "pending" => Numbers::storage($row->getRawOriginal("pending_amount")),
                "overdue" => Numbers::storage($row->overdue_amount ?? 0),
            ])->values(),
        ];

    }

    public static function normalizePaymentModality(?string $modality, string $default = self::PAID_NOW): string {

        return in_array($modality, [self::PAID_NOW, self::CASH_ON_DELIVERY, self::INSTALLMENTS], true)
            ? $modality
            : $default;

    }

    public static function paymentStatus(float $total, float $paid): string {

        $total = Numbers::round($total);
        $paid = Numbers::round($paid);
        $balance = Numbers::subtract($total, $paid);

        return match (true) {
            $paid <= 0 => "unpaid",
            $balance > 0 => "partial",
            $balance < 0 => "overpaid",
            default => "paid"
        };

    }

    public static function createReceivable(
        SaleHeader $sale,
        Collection $paymentLines,
        int $installmentCount,
        ?string $firstDueDate,
        int $userId
    ): ?SaleAccountReceivable {

        if($sale->payment_modality === self::PAID_NOW || Numbers::round($sale->balance_due) <= 0) {

            return null;

        }

        $isInstallmentCredit = $sale->payment_modality === self::INSTALLMENTS;

        $receivablePrincipal = $isInstallmentCredit
            ? Numbers::subtract($sale->balance_due, $sale->installment_extra_amount)
            : Numbers::subtract($sale->total, $sale->installment_extra_amount);

        $receivableTotal = $isInstallmentCredit ? $sale->balance_due : $sale->total;
        $receivablePaid = $isInstallmentCredit ? 0 : $sale->paid_amount;

        $account = SaleAccountReceivable::create([
            "sale_header_id" => $sale->id,
            "customer_id" => $sale->holder_id,
            "currency_id" => $sale->currency_id,
            "issue_date" => $sale->issue_date,
            "due_date" => $firstDueDate,
            "payment_modality" => $sale->payment_modality,
            "original_amount" => $receivablePrincipal,
            "extra_percentage" => $sale->installment_extra_percentage,
            "extra_amount" => $sale->installment_extra_amount,
            "total_amount" => $receivableTotal,
            "paid_amount" => $receivablePaid,
            "pending_amount" => $sale->balance_due,
            "status" => $isInstallmentCredit ? "pending" : ($sale->payment_status === "partial" ? "partial" : "pending"),
            "created_at" => now(),
            "created_by" => $userId,
        ]);

        self::createSaleInstallments($account, max(1, $installmentCount), $firstDueDate, $userId);

        if(!$isInstallmentCredit) {

            self::createSalePaymentTrace($account, $paymentLines, $userId);

        }

        return $account;

    }

    public static function createPayable(
        PurchaseHeader $purchase,
        Collection $paymentLines,
        int $installmentCount,
        ?string $firstDueDate,
        int $userId
    ): ?PurchaseAccountPayable {

        if($purchase->payment_modality === self::PAID_NOW || Numbers::round($purchase->balance_due) <= 0) {

            return null;

        }

        $dueDate = $firstDueDate ?: $purchase->due_date?->toDateString();

        $account = PurchaseAccountPayable::create([
            "purchase_header_id" => $purchase->id,
            "supplier_id" => $purchase->supplier_id,
            "currency_id" => $purchase->currency_id,
            "issue_date" => $purchase->issue_date,
            "due_date" => $dueDate,
            "payment_modality" => $purchase->payment_modality,
            "original_amount" => Numbers::subtract($purchase->total, $purchase->installment_extra_amount),
            "extra_percentage" => $purchase->installment_extra_percentage,
            "extra_amount" => $purchase->installment_extra_amount,
            "total_amount" => $purchase->total,
            "paid_amount" => $purchase->paid_amount,
            "pending_amount" => $purchase->balance_due,
            "status" => $purchase->payment_status === "partial" ? "partial" : "pending",
            "created_at" => now(),
            "created_by" => $userId,
        ]);

        self::createPurchaseInstallments($account, max(1, $installmentCount), $dueDate, $userId);
        self::createPurchasePaymentTrace($account, $paymentLines, $userId);

        return $account;

    }

    private static function createSaleInstallments(SaleAccountReceivable $account, int $count, ?string $firstDueDate, int $userId): void {

        $rows = [];

        foreach(self::installmentAmounts((float) $account->pending_amount, $count) as $number => $lineAmount) {

            $rows[] = [
                "sale_account_receivable_id" => $account->id,
                "installment_number" => $number,
                "due_date" => $firstDueDate ? Carbon::parse($firstDueDate)->addMonthsNoOverflow($number - 1)->toDateString() : null,
                "amount" => $lineAmount,
                "paid_amount" => 0,
                "pending_amount" => $lineAmount,
                "status" => $lineAmount > 0 ? "pending" : "paid",
                "created_at" => now(),
                "created_by" => $userId,
            ];

        }

        SaleReceivableInstallment::insert($rows);

    }

    private static function createPurchaseInstallments(PurchaseAccountPayable $account, int $count, ?string $firstDueDate, int $userId): void {

        $rows = [];

        foreach(self::installmentAmounts((float) $account->pending_amount, $count) as $number => $lineAmount) {

            $rows[] = [
                "purchase_account_payable_id" => $account->id,
                "installment_number" => $number,
                "due_date" => $firstDueDate ? Carbon::parse($firstDueDate)->addMonthsNoOverflow($number - 1)->toDateString() : null,
                "amount" => $lineAmount,
                "paid_amount" => 0,
                "pending_amount" => $lineAmount,
                "status" => $lineAmount > 0 ? "pending" : "paid",
                "created_at" => now(),
                "created_by" => $userId,
            ];

        }

        PurchasePayableInstallment::insert($rows);

    }

    private static function installmentAmounts(float $total, int $count): array {

        $totalUnits = Numbers::toUnits($total);
        $baseUnits = intdiv($totalUnits, $count);
        $extraUnits = $totalUnits % $count;
        $amounts = [];

        for($number = 1; $number <= $count; $number++) {

            $amounts[$number] = Numbers::fromUnits($baseUnits + (int) ($number <= $extraUnits));

        }

        return $amounts;

    }

    private static function createSalePaymentTrace(SaleAccountReceivable $account, Collection $paymentLines, int $userId): void {

        if($paymentLines->isEmpty()) {

            return;

        }

        SaleReceivablePayment::insert($paymentLines
            ->map(fn($payment) => [
                "sale_account_receivable_id" => $account->id,
                "payment_method_id" => $payment["payment_method_id"] ?? null,
                "payment_method_variant_id" => $payment["payment_method_variant_id"] ?? null,
                "paid_at" => now(),
                "amount" => Numbers::storage($payment["amount"] ?? 0),
                "reference" => $payment["reference"] ?? null,
                "observation" => $payment["note"] ?? null,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ])
            ->all());

    }

    private static function createPurchasePaymentTrace(PurchaseAccountPayable $account, Collection $paymentLines, int $userId): void {

        if($paymentLines->isEmpty()) {

            return;

        }

        PurchasePayablePayment::insert($paymentLines
            ->map(fn($payment) => [
                "purchase_account_payable_id" => $account->id,
                "payment_method_id" => $payment["payment_method_id"] ?? null,
                "payment_method_variant_id" => $payment["payment_method_variant_id"] ?? null,
                "paid_at" => now(),
                "amount" => Numbers::storage($payment["amount"] ?? 0),
                "reference" => $payment["reference"] ?? null,
                "observation" => $payment["note"] ?? null,
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ])
            ->all());

    }

}
