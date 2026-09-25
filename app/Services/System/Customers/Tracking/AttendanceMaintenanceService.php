<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Models\System\Customers\{Attendance};
use App\Models\System\Organizations\{Company};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use Carbon\{Carbon};
use Illuminate\Support\Facades\{DB};

final class AttendanceMaintenanceService {
    public static function closeStaleCustomerAttendances(
        ?int $companyId = null,
        int $limit = 500,
        bool $force = false
    ): array {

        $summary = [
            "companies" => 0,
            "closed" => 0,
            "skipped" => 0,
        ];

        $companies = Company::query()
            ->when($companyId, fn($query) => $query->whereKey($companyId))
            ->where("status", "active")
            ->get(["id"]);

        foreach($companies as $company) {

            $summary["companies"]++;

            if(!self::isAutoCloseEnabled((int) $company->id) && !$force) {

                $summary["skipped"]++;

                continue;

            }

            if(!self::canRunAutoCloseNow((int) $company->id) && !$force) {

                $summary["skipped"]++;

                continue;

            }

            $summary["closed"] += self::closeCompanyAttendances((int) $company->id, $limit);

        }

        return $summary;

    }

    public static function pruneCustomerAttendances(
        ?int $companyId = null,
        ?int $months = null,
        int $limit = 1000,
        bool $dryRun = false
    ): array {

        $summary = [
            "companies" => 0,
            "eligible" => 0,
            "deleted" => 0,
            "dry_run" => $dryRun,
        ];

        $companies = Company::query()
            ->when($companyId, fn($query) => $query->whereKey($companyId))
            ->where("status", "active")
            ->get(["id"]);

        foreach($companies as $company) {

            $summary["companies"]++;
            $retentionMonths = max(4, (int) ($months ?? CompanySettingService::value(
                (int) $company->id,
                CompanySettingService::CUSTOMER_ATTENDANCE,
                "retention_months",
                5
            )));

            $cutoff = now()->subMonths($retentionMonths);

            $query = Attendance::query()
                ->whereIn("status", ["finalized", "canceled", "inactive", "absent"])
                ->where(function($query) use ($cutoff) {

                    $query->where("end_date", "<", $cutoff)
                        ->orWhere(function($query) use ($cutoff) {

                            $query->whereNull("end_date")
                                ->where("created_at", "<", $cutoff);

                        });

                })
                ->limit($limit);

            $ids = $query->pluck("id");
            $summary["eligible"] += $ids->count();

            if(!$dryRun && $ids->isNotEmpty()) {

                $summary["deleted"] += Attendance::query()
                    ->whereIn("id", $ids)
                    ->delete();

            }

        }

        return $summary;

    }

    private static function closeCompanyAttendances(int $companyId, int $limit): int {

        return DB::transaction(function() use ($companyId, $limit) {

            $maxActiveHours = max(1, (int) CompanySettingService::value(
                $companyId,
                CompanySettingService::CUSTOMER_ATTENDANCE,
                "max_active_hours",
                20
            ));

            $todayStart = now()->startOfDay();
            $expiredAt = now()->subHours($maxActiveHours);

            $attendances = Attendance::query()
                ->where("status", "active")
                ->where(function($query) use ($todayStart, $expiredAt) {

                    $query->where("start_date", "<", $todayStart)
                        ->orWhere("start_date", "<=", $expiredAt);

                })
                ->orderBy("start_date")
                ->lockForUpdate()
                ->limit($limit)
                ->get();

            foreach($attendances as $attendance) {

                $closeAt = self::technicalCloseDate((string) $attendance->start_date, $companyId);

                if($closeAt->greaterThan(now())) {

                    $closeAt = now();

                }
                $note = "Marcada como ausente por cierre automático: no registró salida antes del corte operativo.";

                $currentObservation = trim((string) $attendance->observation);

                $attendance->end_date = $closeAt;
                $attendance->status = "absent";
                $attendance->observation = trim($currentObservation === "" ? $note : "{$currentObservation}\n{$note}");
                $attendance->updated_at = now();
                $attendance->updated_by = null;
                $attendance->save();

            }

            return $attendances->count();

        });

    }

    private static function isAutoCloseEnabled(int $companyId): bool {

        return (bool) CompanySettingService::value(
            $companyId,
            CompanySettingService::CUSTOMER_ATTENDANCE,
            "auto_close_stale_enabled",
            true
        );

    }

    private static function canRunAutoCloseNow(int $companyId): bool {

        $timezone = (string) CompanySettingService::value($companyId, "localization", "timezone", "America/Lima");
        $afterTime = (string) CompanySettingService::value(
            $companyId,
            CompanySettingService::CUSTOMER_ATTENDANCE,
            "auto_close_after_time",
            "01:00"
        );

        $now = Carbon::now($timezone);
        $minimum = Carbon::parse($now->toDateString()." {$afterTime}", $timezone);

        return $now->greaterThanOrEqualTo($minimum);

    }

    private static function technicalCloseDate(string $startDate, int $companyId): Carbon {

        $endTime = (string) CompanySettingService::value(
            $companyId,
            CompanySettingService::CUSTOMER_ATTENDANCE,
            "auto_close_end_time",
            "23:50"
        );

        $start = Carbon::parse($startDate);
        $closeAt = Carbon::parse($start->format("Y-m-d")." {$endTime}");

        return $closeAt->greaterThan($start) ? $closeAt : $start->copy()->addHours(20);

    }
}
