<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Customers\{Attendance, AttendanceCorrection};
use App\Models\System\Organizations\{Branch};
use DomainException;
use Illuminate\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Collection};

/**
 * Service class for managing Tracking Attendance operations
 * Handles business logic for listing and managing attendances
 */
class TrackingAttendanceService {
    private const MAX_EXPORT_ROWS = 10000;

    /**
     * Translation namespace for tracking attendance module
     */
    private const TRANSLATION_NAMESPACE = "System.Customers.tracking_attendance";

    /**
     * Get translation with fallback
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    private static function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback(self::TRANSLATION_NAMESPACE, $key, $replace);

    }

    /**
     * Get paginated list of attendances
     *
     * @param  array  $filters Filter parameters
     * @param  int  $perPage Items per page
     */
    public static function getPaginatedList(
        array $filters = [],
        int $perPage = 15,
        ?array $allowedBranchIds = null
    ): LengthAwarePaginator {

        $query = self::query($filters, $allowedBranchIds);

        if($query === null) {

            return new LengthAwarePaginator([], 0, 1, 1, ["path" => ""]);

        }

        return $query->paginate($perPage);

    }

    public static function getForExport(
        array $filters = [],
        ?array $allowedBranchIds = null
    ): Collection {

        $query = self::query($filters, $allowedBranchIds);

        if($query === null) {

            return collect();

        }

        $records = $query->limit(self::MAX_EXPORT_ROWS + 1)->get();

        if($records->count() > self::MAX_EXPORT_ROWS) {

            throw new DomainException("La exportación supera 10 000 registros. Reduce el rango de fechas.");

        }

        return $records;

    }

    private static function query(array $filters, ?array $allowedBranchIds) {

        $branch = Branch::query()
            ->where("id", $filters["branch_id"] ?? null)
            ->first();

        if(!$branch || ($allowedBranchIds !== null && !in_array((int) $branch->id, $allowedBranchIds, true))) {

            return null;

        }

        $query = Attendance::query()
            ->where("branch_id", $branch->id)
            ->when($allowedBranchIds !== null, fn($query) => $query->whereIn("branch_id", $allowedBranchIds));

        if(Utilities::isDefined($filters["start_date"] ?? null)) {

            $query->where("start_date", ">=", Utilities::startOfDay($filters["start_date"]));

        }else {

            $query->whereBetween("start_date", [
                Utilities::startOfDay(date("Y-m-d")),
                Utilities::endOfDay(date("Y-m-d")),
            ]);

        }

        if(Utilities::isDefined($filters["end_date"] ?? null)) {

            $query->where("start_date", "<=", Utilities::endOfDay($filters["end_date"]));

        }

        self::applyFilters($query, $filters);

        return $query
            ->orderByDesc("id")
            ->with(["branch", "customer", "biometricDevice", "corrections"]);

    }

    /**
     * Apply filters to query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private static function applyFilters($query, array $filters): void {

        if(Utilities::isDefined($filters["customer_id"])) {

            $query->where("customer_id", $filters["customer_id"]);

        }

        if(Utilities::isDefined($filters["status"])) {

            $query->where("status", $filters["status"]);

        }

    }

    /**
     * Cancel an attendance
     *
     * @param  Attendance  $attendance Attendance instance
     * @param  string|null  $motive Cancellation motive
     * @param  int|null  $userId User ID
     *
     * @throws \Exception
     */
    public static function cancel(Attendance $attendance, ?string $motive = null, ?int $userId = null): Attendance {

        if(!in_array($attendance->status, ["active", "finalized"])) {

            throw new \Exception("La asistencia no puede ser anulada.");

        }

        $attendance->motive = $motive ?? "N/A";

        $attendance->status = "canceled";
        $attendance->updated_at = now();
        $attendance->updated_by = $userId;
        $attendance->canceled_at = now();
        $attendance->canceled_by = $userId;
        $attendance->save();

        return $attendance;

    }

    public static function requestCorrection(Attendance $attendance, array $data, ?int $userId): AttendanceCorrection {

        if(AttendanceCorrection::query()
            ->where("attendance_id", $attendance->id)
            ->where("status", "pending")
            ->exists()) {

            throw new DomainException("La asistencia ya tiene una corrección pendiente de revisión.");

        }

        return AttendanceCorrection::create([
            "attendance_id" => $attendance->id,
            "requested_by" => $userId,
            "previous_start_date" => $attendance->start_date,
            "previous_end_date" => $attendance->end_date,
            "requested_start_date" => $data["start_date"] ?? $attendance->start_date,
            "requested_end_date" => $data["end_date"] ?? $attendance->end_date,
            "reason" => $data["reason"],
            "status" => "pending",
        ]);

    }

    public static function reviewCorrection(
        AttendanceCorrection $correction,
        string $decision,
        ?string $note,
        ?int $userId
    ): AttendanceCorrection {

        if($correction->status !== "pending") {

            throw new DomainException("La corrección ya fue revisada.");

        }

        return DB::transaction(function() use ($correction, $decision, $note, $userId) {

            $correction->loadMissing("attendance");

            if($decision === "approved") {

                $correction->attendance->forceFill([
                    "start_date" => $correction->requested_start_date,
                    "end_date" => $correction->requested_end_date,
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ])->save();

            }

            $correction->forceFill([
                "status" => $decision,
                "reviewed_by" => $userId,
                "review_note" => $note,
                "reviewed_at" => now(),
            ])->save();

            return $correction->fresh(["attendance", "requestedBy", "reviewedBy"]);

        });

    }
}
