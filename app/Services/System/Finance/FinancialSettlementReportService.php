<?php

declare(strict_types=1);

namespace App\Services\System\Finance;

use App\Helpers\System\{Utilities};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Collection};

final class FinancialSettlementReportService {
    public static function summarize(
        int $companyId,
        string $type,
        string $scope,
        ?string $dateFrom,
        ?string $dateTo
    ): Collection {

        $scopes = $scope === "both" ? ["sale", "purchase"] : [$scope];

        return collect($scopes)
            ->flatMap(fn($currentScope) => $type === "payments"
                ? self::payments($companyId, $currentScope, $dateFrom, $dateTo)
                : self::taxes($companyId, $currentScope, $dateFrom, $dateTo)
            )
            ->values();

    }

    private static function taxes(int $companyId, string $scope, ?string $from, ?string $to): Collection {

        [$detailTable, $headerTable, $foreignKey, $dateColumn] = $scope === "purchase"
            ? ["purchase_taxes", "purchase_headers", "purchase_header_id", "issue_date"]
            : ["sale_taxes", "sales_header", "sale_header_id", "issue_date"];

        return DB::table("{$detailTable} as detail")
            ->join("{$headerTable} as header", "header.id", "=", "detail.{$foreignKey}")
            ->where("detail.status", "active")
            ->when($from, fn($query) => $query->where("header.{$dateColumn}", ">=", Utilities::startOfDay($from)))
            ->when($to, fn($query) => $query->where("header.{$dateColumn}", "<=", Utilities::endOfDay($to)))
            ->groupBy("detail.tax_id", "detail.name", "detail.calculation_type", "detail.operation_type")
            ->selectRaw("? as scope, detail.tax_id, detail.name, detail.calculation_type, detail.operation_type, COUNT(DISTINCT header.id) as documents, SUM(detail.quantity) as quantity, SUM(detail.base_amount) as base_amount, SUM(detail.amount) as amount", [$scope])
            ->get();

    }

    private static function payments(int $companyId, string $scope, ?string $from, ?string $to): Collection {

        [$detailTable, $headerTable, $foreignKey, $dateColumn] = $scope === "purchase"
            ? ["purchase_payments", "purchase_headers", "purchase_header_id", "issue_date"]
            : ["sale_payments", "sales_header", "sale_header_id", "issue_date"];

        return DB::table("{$detailTable} as detail")
            ->join("{$headerTable} as header", "header.id", "=", "detail.{$foreignKey}")
            ->where("detail.status", "active")
            ->when($from, fn($query) => $query->where("header.{$dateColumn}", ">=", Utilities::startOfDay($from)))
            ->when($to, fn($query) => $query->where("header.{$dateColumn}", "<=", Utilities::endOfDay($to)))
            ->groupBy("detail.payment_method_id", "detail.name")
            ->selectRaw("? as scope, detail.payment_method_id, detail.name, COUNT(DISTINCT header.id) as documents, SUM(detail.amount) as amount", [$scope])
            ->get();

    }
}
