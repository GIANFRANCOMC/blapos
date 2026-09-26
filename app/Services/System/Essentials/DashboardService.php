<?php

declare(strict_types=1);

namespace App\Services\System\Essentials;

use Carbon\{CarbonImmutable};
use Illuminate\Support\Facades\{DB};

final class DashboardService {
    public static function getDashboardData(string $date, ?int $branchId = null): array {

        $timezone = (string) (DB::table("company_settings")
            ->where("group", "localization")
            ->where("key", "timezone")
            ->where("status", "active")
            ->value("value") ?: "America/Lima");

        $expirationWindow = max(1, (int) (DB::table("company_settings")
            ->where("group", "dashboard")
            ->where("key", "membership_expiration_window_days")
            ->where("status", "active")
            ->value("value") ?: 7));

        $day = CarbonImmutable::parse($date, $timezone);
        $dayStart = $day->startOfDay();
        $dayEnd = $day->endOfDay();

        $salesBase = DB::table("sales_header")
            ->join("series", "series.id", "=", "sales_header.serie_id")
            ->when($branchId, fn($query) => $query->where("series.branch_id", $branchId));

        $netSales = (clone $salesBase)
            ->where("sales_header.status", "active")
            ->whereBetween("sales_header.issue_date", [$dayStart, $dayEnd])
            ->selectRaw("COUNT(sales_header.id) as count, COALESCE(SUM(sales_header.total), 0) as total")
            ->first();

        $canceledSales = (clone $salesBase)
            ->where("sales_header.status", "canceled")
            ->whereBetween("sales_header.canceled_at", [$dayStart, $dayEnd])
            ->selectRaw("COUNT(sales_header.id) as count, COALESCE(SUM(sales_header.total), 0) as total")
            ->first();

        $attendances = DB::table("attendances")
            ->when($branchId, fn($query) => $query->where("branch_id", $branchId))
            ->whereIn("status", ["active", "finalized"])
            ->whereBetween("start_date", [$dayStart, $dayEnd])
            ->count();

        $expiringSubscriptions = DB::table("subscriptions")
            ->when($branchId, fn($query) => $query->where("branch_id", $branchId))
            ->where("status", "active")
            ->whereBetween("end_date", [
                $dayStart->toDateString(),
                $dayStart->addDays($expirationWindow - 1)->toDateString(),
            ])
            ->count();

        return [
            "date" => $day->toDateString(),
            "timezone" => $timezone,
            "branch_id" => $branchId,
            "sales" => [
                "net" => ["count" => (int) $netSales->count, "total" => (float) $netSales->total],
                "canceled" => ["count" => (int) $canceledSales->count, "total" => (float) $canceledSales->total],
            ],
            "attendances" => ["count" => $attendances],
            "expiring_subscriptions" => [
                "count" => $expiringSubscriptions,
                "window_days" => $expirationWindow,
            ],
            "branches" => [
                "active_count" => DB::table("branches")
                    ->where("status", "active")
                    ->count(),
            ],
            "users" => [
                "active_count" => DB::table("users")
                    ->where("status", "active")
                    ->count(),
            ],
        ];

    }
}
