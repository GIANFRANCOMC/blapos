<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Users;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{Branch, User, UserAttendance, UserAttendanceBreak, UserAttendanceCorrection};
use App\Services\System\Devices\BiometricDevices\{BiometricDeviceService};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Carbon\{Carbon};
use DomainException;
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Support\Facades\{DB};

final class UserAttendanceService {
    public const SOURCE_MANUAL = "manual_form";

    public const SOURCE_QR_CAMERA = "qr_camera";

    public const SOURCE_QR_SCANNER = "qr_scanner";

    public const SOURCE_BIOMETRIC = "biometric";

    public const SOURCE_SYSTEM = "system";

    public const STATUS_ACTIVE = "active";

    public const STATUS_FINALIZED = "finalized";

    public const STATUS_CANCELED = "canceled";

    public static function sourceTypes(): array {

        return [
            self::SOURCE_MANUAL,
            self::SOURCE_QR_CAMERA,
            self::SOURCE_QR_SCANNER,
            self::SOURCE_BIOMETRIC,
            self::SOURCE_SYSTEM,
        ];

    }

    public static function checkIn(array $data): UserAttendance {

        return DB::transaction(function() use ($data) {

            $companyId = app(TenantCompanyContext::class)->id();
            $branchId = (int) $data["branch_id"];
            $userId = (int) $data["user_id"];
            $actorId = isset($data["actor_id"]) ? (int) $data["actor_id"] : null;
            $checkedInAt = Carbon::parse($data["checked_in_at"] ?? now());

            self::lockActiveUser($companyId, $userId);
            self::requireActiveBranch($companyId, $branchId);
            self::requireActorBranchAccess($companyId, $actorId, $branchId);

            $activeAttendance = UserAttendance::query()
                ->where("user_id", $userId)
                ->where("status", self::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if($activeAttendance) {

                $branchName = $activeAttendance->branch()->value("name") ?? "otra sucursal";
                throw new DomainException("El colaborador ya tiene una jornada en curso en {$branchName}.");

            }

            return UserAttendance::create([
                "branch_id" => $branchId,
                "user_id" => $userId,
                "work_date" => $checkedInAt->toDateString(),
                "checked_in_at" => $checkedInAt,
                "worked_minutes" => 0,
                "source_type" => $data["source_type"] ?? self::SOURCE_MANUAL,
                "source_reference" => $data["source_reference"] ?? null,
                "observation" => $data["observation"] ?? null,
                "status" => self::STATUS_ACTIVE,
                "created_at" => now(),
                "created_by" => $actorId,
            ]);

        });

    }

    public static function checkInFromBiometric(array $data): UserAttendance {

        $companyId = app(TenantCompanyContext::class)->id();
        $deviceId = (int) $data["device_id"];
        $deviceUserId = (int) $data["device_user_id"];
        $branchId = (int) $data["branch_id"];
        $device = BiometricDeviceService::findByIdInTenant($deviceId, $companyId, ["active"]);

        if(!$device || (int) $device->branch_id !== $branchId) {

            throw new DomainException("El dispositivo biométrico no está activo en la sucursal seleccionada.");

        }

        $user = BiometricDeviceService::findUserByDeviceUserId($deviceId, $deviceUserId);

        if(!$user) {

            throw new DomainException("No se encontró un colaborador activo para la identidad biométrica.");

        }

        return self::checkIn([
            ...$data,
            "user_id" => (int) $user->id,
            "source_type" => self::SOURCE_BIOMETRIC,
            "source_reference" => "device:{$deviceId};user:{$deviceUserId}",
        ]);

    }

    public static function checkOut(array $data): UserAttendance {

        return DB::transaction(function() use ($data) {

            $companyId = app(TenantCompanyContext::class)->id();
            $branchId = (int) $data["branch_id"];
            $userId = (int) $data["user_id"];
            $actorId = isset($data["actor_id"]) ? (int) $data["actor_id"] : null;
            $checkedOutAt = Carbon::parse($data["checked_out_at"] ?? now());

            self::lockActiveUser($companyId, $userId);
            self::requireActiveBranch($companyId, $branchId);
            self::requireActorBranchAccess($companyId, $actorId, $branchId);

            $attendance = UserAttendance::query()
                ->where("user_id", $userId)
                ->where("status", self::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if(!$attendance) {

                throw new DomainException("El colaborador no tiene una jornada en curso.");

            }

            if((int) $attendance->branch_id !== $branchId) {

                throw new DomainException("La jornada en curso pertenece a otra sucursal.");

            }

            if(UserAttendanceBreak::query()
                ->where("user_attendance_id", $attendance->id)
                ->where("status", "active")
                ->exists()) {

                throw new DomainException("Finaliza la pausa en curso antes de registrar la salida.");

            }

            $checkedInAt = Carbon::parse($attendance->checked_in_at);

            if($checkedOutAt->lessThanOrEqualTo($checkedInAt)) {

                throw new DomainException("La salida debe ser posterior al ingreso.");

            }

            $attendance->checked_out_at = $checkedOutAt;

            $metrics = self::calculateMetrics($attendance, $checkedInAt, $checkedOutAt);
            $attendance->worked_minutes = $metrics["worked_minutes"];
            $attendance->ordinary_minutes = $metrics["ordinary_minutes"];
            $attendance->late_minutes = $metrics["late_minutes"];
            $attendance->overtime_minutes = $metrics["overtime_minutes"];
            $attendance->break_minutes = $metrics["break_minutes"];
            $attendance->status = self::STATUS_FINALIZED;
            $attendance->updated_at = now();
            $attendance->updated_by = $actorId;
            $attendance->save();

            return $attendance->fresh(["branch", "user"]);

        });

    }

    public static function startBreak(int $companyId, int $attendanceId, int $actorId, ?string $reason = null): UserAttendanceBreak {

        return DB::transaction(function() use ($companyId, $attendanceId, $actorId, $reason) {

            $attendance = UserAttendance::query()
                ->where("status", self::STATUS_ACTIVE)
                ->lockForUpdate()
                ->find($attendanceId);

            if(!$attendance) {

                throw new DomainException("La jornada no está disponible para iniciar una pausa.");

            }

            self::requireActorBranchAccess($companyId, $actorId, (int) $attendance->branch_id);

            if(UserAttendanceBreak::query()
                ->where("user_attendance_id", $attendanceId)
                ->where("status", "active")
                ->exists()) {

                throw new DomainException("La jornada ya tiene una pausa en curso.");

            }

            return UserAttendanceBreak::create([
                "user_attendance_id" => $attendanceId,
                "started_at" => now(),
                "reason" => $reason,
                "status" => "active",
                "created_by" => $actorId,
            ]);

        });

    }

    public static function endBreak(int $companyId, int $attendanceId, int $actorId): UserAttendanceBreak {

        return DB::transaction(function() use ($companyId, $attendanceId, $actorId) {

            $attendance = UserAttendance::query()
                ->lockForUpdate()
                ->find($attendanceId);

            if(!$attendance) {

                throw new DomainException("La jornada no existe o pertenece a otra empresa.");

            }

            self::requireActorBranchAccess($companyId, $actorId, (int) $attendance->branch_id);

            $break = UserAttendanceBreak::query()
                ->where("user_attendance_id", $attendanceId)
                ->where("status", "active")
                ->lockForUpdate()
                ->first();

            if(!$break) {

                throw new DomainException("La jornada no tiene una pausa en curso.");

            }

            $break->ended_at = now();

            $break->duration_minutes = $break->started_at->diffInMinutes($break->ended_at);
            $break->status = "finalized";
            $break->updated_by = $actorId;
            $break->save();

            return $break;

        });

    }

    public static function requestCorrection(int $companyId, int $attendanceId, int $actorId, array $data): UserAttendanceCorrection {

        return DB::transaction(function() use ($companyId, $attendanceId, $actorId, $data) {

            $attendance = UserAttendance::query()
                ->lockForUpdate()
                ->find($attendanceId);

            if(!$attendance) {

                throw new DomainException("La jornada no existe o pertenece a otra empresa.");

            }

            self::requireActorBranchAccess($companyId, $actorId, (int) $attendance->branch_id);

            return UserAttendanceCorrection::create([
                "user_attendance_id" => $attendanceId,
                "requested_by" => $actorId,
                "requested_check_in_at" => $data["checked_in_at"] ?? null,
                "requested_check_out_at" => $data["checked_out_at"] ?? null,
                "reason" => $data["reason"],
                "status" => "pending",
            ]);

        });

    }

    public static function reviewCorrection(int $companyId, int $correctionId, int $actorId, bool $approve, ?string $note): UserAttendanceCorrection {

        return DB::transaction(function() use ($companyId, $correctionId, $actorId, $approve, $note) {

            $correction = UserAttendanceCorrection::query()
                ->where("status", "pending")
                ->lockForUpdate()
                ->find($correctionId);

            if(!$correction) {

                throw new DomainException("La solicitud de corrección ya fue revisada o no existe.");

            }

            $attendance = UserAttendance::query()
                ->lockForUpdate()
                ->find($correction->user_attendance_id);

            if(!$attendance) {

                throw new DomainException("La jornada asociada ya no está disponible.");

            }

            self::requireActorBranchAccess($companyId, $actorId, (int) $attendance->branch_id);

            if($approve) {

                $attendance->checked_in_at = $correction->requested_check_in_at ?? $attendance->checked_in_at;
                $attendance->checked_out_at = $correction->requested_check_out_at ?? $attendance->checked_out_at;

                if($attendance->checked_out_at && Carbon::parse($attendance->checked_out_at)->lessThanOrEqualTo(Carbon::parse($attendance->checked_in_at))) {

                    throw new DomainException("La salida corregida debe ser posterior al ingreso.");

                }

                if($attendance->checked_out_at) {

                    $metrics = self::calculateMetrics(
                        $attendance,
                        Carbon::parse($attendance->checked_in_at),
                        Carbon::parse($attendance->checked_out_at)
                    );

                    $attendance->fill($metrics);

                }

                $attendance->updated_at = now();

                $attendance->updated_by = $actorId;
                $attendance->save();

            }

            $correction->status = $approve ? "approved" : "rejected";

            $correction->reviewed_by = $actorId;
            $correction->review_note = $note;
            $correction->reviewed_at = now();
            $correction->save();

            return $correction;

        });

    }

    public static function getPaginatedList(
        int $companyId,
        array $filters = [],
        int $perPage = 15,
        ?array $allowedBranchIds = null
    ): LengthAwarePaginator {

        return self::getFilteredQuery($companyId, $filters, $allowedBranchIds)
            ->orderByDesc("checked_in_at")
            ->paginate($perPage);

    }

    public static function getFilteredQuery(
        int $companyId,
        array $filters = [],
        ?array $allowedBranchIds = null
    ): Builder {

        $query = UserAttendance::query()
            ->with([
                "branch",
                "user",
                "breaks" => fn($query) => $query->orderByDesc("started_at"),
                "corrections" => fn($query) => $query->orderByDesc("id"),
            ]);

        if($allowedBranchIds !== null) {

            $query->whereIn("branch_id", $allowedBranchIds);

        }

        if(!empty($filters["branch_id"])) {

            $query->where("branch_id", (int) $filters["branch_id"]);

        }

        if(!empty($filters["user_id"])) {

            $query->where("user_id", (int) $filters["user_id"]);

        }

        if(!empty($filters["status"])) {

            $query->where("status", (string) $filters["status"]);

        }

        if(!empty($filters["date_from"])) {

            $query->where("work_date", ">=", $filters["date_from"]);

        }

        if(!empty($filters["date_to"])) {

            $query->where("work_date", "<=", $filters["date_to"]);

        }

        return $query;

    }

    public static function weeklySummary(
        int $companyId,
        int $userId,
        ?string $weekStart = null,
        ?int $branchId = null,
        ?array $allowedBranchIds = null
    ): array {

        $start = Carbon::parse($weekStart ?? now())->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

        $query = UserAttendance::query()
            ->where("user_id", $userId)
            ->where("status", self::STATUS_FINALIZED)
            ->whereBetween("work_date", [$start->toDateString(), $end->toDateString()]);

        if($allowedBranchIds !== null) {

            $query->whereIn("branch_id", $allowedBranchIds);

        }

        if($branchId) {

            $query->where("branch_id", $branchId);

        }

        $records = $query->orderBy("work_date")->get();

        $totalMinutes = (int) $records->sum("worked_minutes");

        return [
            "week_start" => $start->toDateString(),
            "week_end" => $end->toDateString(),
            "total_minutes" => $totalMinutes,
            "total_hours" => Utilities::round($totalMinutes / 60, null, $companyId),
            "days" => $records
                ->groupBy(fn(UserAttendance $attendance) => $attendance->work_date->toDateString())
                ->map(fn($dayRecords, $date) => [
                    "date" => $date,
                    "worked_minutes" => (int) $dayRecords->sum("worked_minutes"),
                    "worked_hours" => Utilities::round(((int) $dayRecords->sum("worked_minutes")) / 60, null, $companyId),
                ])
                ->values()
                ->all(),
        ];

    }

    private static function lockActiveUser(int $companyId, int $userId): User {

        $user = User::query()
            ->where("status", "active")
            ->lockForUpdate()
            ->find($userId);

        if(!$user) {

            throw new DomainException("El colaborador no está activo o no pertenece a la empresa.");

        }

        return $user;

    }

    private static function requireActiveBranch(int $companyId, int $branchId): Branch {

        $branch = Branch::query()
            ->where("status", "active")
            ->find($branchId);

        if(!$branch) {

            throw new DomainException("La sucursal no está activa o no pertenece a la empresa.");

        }

        return $branch;

    }

    private static function requireActorBranchAccess(int $companyId, ?int $actorId, int $branchId): void {

        if(!$actorId) {

            return;

        }

        $actor = User::query()
            ->where("status", "active")
            ->find($actorId);

        if(!$actor || !AccessScopeService::canAccess($actor, AccessScopeService::BRANCH, $branchId)) {

            throw new DomainException("No tienes acceso operativo a la sucursal de esta jornada.");

        }

    }

    private static function calculateMetrics(UserAttendance $attendance, Carbon $checkIn, Carbon $checkOut): array {

        $breakMinutes = (int) UserAttendanceBreak::query()
            ->where("user_attendance_id", $attendance->id)
            ->where("status", "finalized")
            ->sum("duration_minutes");

        $workedMinutes = max(0, $checkIn->diffInMinutes($checkOut) - $breakMinutes);
        $weekday = $checkIn->dayOfWeekIso;
        $schedule = DB::table("user_work_schedules")
            ->where("weekday", $weekday)
            ->where("status", "active")
            ->where(function($query) use ($attendance) {

                $query->where("user_id", $attendance->user_id)->orWhereNull("user_id");

            })
            ->where(function($query) use ($attendance) {

                $query->where("branch_id", $attendance->branch_id)->orWhereNull("branch_id");

            })
            ->orderByRaw("user_id is null")
            ->orderByRaw("branch_id is null")
            ->first();

        if(!$schedule || !$schedule->is_working_day) {

            return [
                "worked_minutes" => $workedMinutes,
                "ordinary_minutes" => 0,
                "late_minutes" => 0,
                "overtime_minutes" => $workedMinutes,
                "break_minutes" => $breakMinutes,
            ];

        }

        $scheduledStart = Carbon::parse($checkIn->toDateString()." ".$schedule->starts_at);

        $scheduledEnd = Carbon::parse($checkIn->toDateString()." ".$schedule->ends_at);

        if($schedule->crosses_midnight || $scheduledEnd->lessThanOrEqualTo($scheduledStart)) {

            $scheduledEnd->addDay();

        }

        $scheduledMinutes = $scheduledStart->diffInMinutes($scheduledEnd);

        $lateMinutes = max(0, $scheduledStart->copy()->addMinutes((int) $schedule->tolerance_minutes)->diffInMinutes($checkIn, false));

        return [
            "worked_minutes" => $workedMinutes,
            "ordinary_minutes" => min($workedMinutes, $scheduledMinutes),
            "late_minutes" => $lateMinutes,
            "overtime_minutes" => max(0, $workedMinutes - $scheduledMinutes),
            "break_minutes" => $breakMinutes,
        ];

    }
}
