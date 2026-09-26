<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Organizations;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Organizations\UserAttendances\{BiometricCheckInRequest, CheckInUserAttendanceRequest, CheckOutUserAttendanceRequest, RequestUserAttendanceCorrectionRequest, ReviewUserAttendanceCorrectionRequest, StartUserAttendanceBreakRequest, UserAttendanceSummaryRequest};
use App\Services\System\Base\{CompanyReferenceDataService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Organizations\Users\{UserAttendanceConfigService, UserAttendanceService};
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Validation\{ValidationException};

final class UserAttendanceController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Organizations.user_attendance";

    public function index() {

        return view("System/general/Organizations/user_attendances/main");

    }

    public function initParams(Request $request): JsonResponse {

        return response()->json(
            UserAttendanceConfigService::getInitParams(
                (string) $request->input("page", "main"),
                $this->getUserId()
            )
        );

    }

    public function list(Request $request) {

        return response()->json([
            "bool" => true,
            "data" => UserAttendanceService::getPaginatedList(
                $this->getCompanyId(),
                [
                    "branch_id" => $request->input("branch_id"),
                    "user_id" => $request->input("user_id"),
                    "status" => $request->input("status"),
                    "date_from" => $request->input("date_from"),
                    "date_to" => $request->input("date_to"),
                ],
                $this->getPerPage($request),
                $this->allowedBranchIds()
            ),
        ]);

    }

    public function export(Request $request): Response {

        $filters = [
            "branch_id" => $request->input("branch_id"),
            "user_id" => $request->input("user_id"),
            "status" => $request->input("status"),
            "date_from" => $request->input("date_from"),
            "date_to" => $request->input("date_to"),
        ];

        $query = UserAttendanceService::getFilteredQuery(
            $this->getCompanyId(),
            $filters,
            $this->allowedBranchIds()
        );

        $limit = max(100, (int) CompanySettingService::value(
            "reports",
            "export_max_rows",
            25000
        ));

        if((clone $query)->limit($limit + 1)->count() > $limit) {

            throw ValidationException::withMessages([
                "filters" => "El reporte supera {$limit} registros. Reduce el rango o aplica más filtros.",
            ]);

        }

        $handle = fopen("php://temp", "r+");
        fputcsv($handle, [
            "Fecha", "Colaborador", "Sucursal", "Ingreso", "Salida", "Horas trabajadas",
            "Minutos ordinarios", "Tardanza", "Horas extra", "Pausas", "Estado",
        ], ";");

        foreach($query->orderByDesc("checked_in_at")->get() as $attendance) {

            fputcsv($handle, [
                $attendance->work_date?->format("Y-m-d"),
                $attendance->user?->name,
                $attendance->branch?->name,
                $attendance->checked_in_at?->format("Y-m-d H:i:s"),
                $attendance->checked_out_at?->format("Y-m-d H:i:s"),
                number_format((float) $attendance->worked_hours, 2, ".", ""),
                (int) $attendance->ordinary_minutes,
                (int) $attendance->late_minutes,
                (int) $attendance->overtime_minutes,
                (int) $attendance->break_minutes,
                $attendance->status,
            ], ";");

        }

        rewind($handle);

        $csv = stream_get_contents($handle);
        fclose($handle);

        return response("\xEF\xBB\xBF".$csv, 200, [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=blapos-asistencia-laboral-".now()->format("Ymd-His").".csv",
        ]);

    }

    public function checkIn(CheckInUserAttendanceRequest $request): JsonResponse {

        try {

            $attendance = UserAttendanceService::checkIn([
                ...$request->validated(),
                "actor_id" => $this->getUserId(),
            ]);

            return response()->json([
                "bool" => true,
                "msg" => "Ingreso del colaborador registrado correctamente.",
                "attendance" => $attendance->load(["branch", "user"]),
            ]);

        }catch(\DomainException $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }catch(\Exception $exception) {

            return $this->handleException($exception, "check_in");

        }

    }

    public function checkOut(CheckOutUserAttendanceRequest $request): JsonResponse {

        try {

            $attendance = UserAttendanceService::checkOut([
                ...$request->validated(),
                "actor_id" => $this->getUserId(),
            ]);

            return response()->json([
                "bool" => true,
                "msg" => "Salida registrada. La jornada quedó finalizada.",
                "attendance" => $attendance,
            ]);

        }catch(\DomainException $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }catch(\Exception $exception) {

            return $this->handleException($exception, "check_out");

        }

    }

    public function biometricCheckIn(BiometricCheckInRequest $request): JsonResponse {

        $data = $request->validated();

        try {

            $attendance = UserAttendanceService::checkInFromBiometric([
                ...$data,
                "actor_id" => $this->getUserId(),
            ]);

            return response()->json([
                "bool" => true,
                "msg" => "Ingreso biométrico registrado correctamente.",
                "attendance" => $attendance->load(["branch", "user"]),
            ]);

        }catch(\DomainException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function weeklySummary(UserAttendanceSummaryRequest $request): JsonResponse {

        $data = $request->validated();

        return response()->json([
            "bool" => true,
            "summary" => UserAttendanceService::weeklySummary(
                $this->getCompanyId(),
                (int) $data["user_id"],
                $data["week_start"] ?? null,
                isset($data["branch_id"]) ? (int) $data["branch_id"] : null,
                $this->allowedBranchIds()
            ),
        ]);

    }

    public function startBreak(StartUserAttendanceBreakRequest $request, int $attendanceId): JsonResponse {

        $data = $request->validated();

        return $this->domainResponse(function() use ($attendanceId, $data) {

            $break = UserAttendanceService::startBreak(
                $this->getCompanyId(),
                $attendanceId,
                $this->getUserId(),
                $data["reason"] ?? null
            );

            return ["msg" => "Pausa iniciada correctamente.", "break" => $break];

        });

    }

    public function endBreak(int $attendanceId): JsonResponse {

        return $this->domainResponse(function() use ($attendanceId) {

            $break = UserAttendanceService::endBreak(
                $this->getCompanyId(),
                $attendanceId,
                $this->getUserId()
            );

            return ["msg" => "Pausa finalizada correctamente.", "break" => $break];

        });

    }

    public function requestCorrection(RequestUserAttendanceCorrectionRequest $request, int $attendanceId): JsonResponse {

        $data = $request->validated();

        return $this->domainResponse(function() use ($attendanceId, $data) {

            $correction = UserAttendanceService::requestCorrection(
                $this->getCompanyId(),
                $attendanceId,
                $this->getUserId(),
                $data
            );

            return ["msg" => "Solicitud de corrección registrada.", "correction" => $correction];

        });

    }

    public function reviewCorrection(ReviewUserAttendanceCorrectionRequest $request, int $correctionId): JsonResponse {

        $data = $request->validated();

        return $this->domainResponse(function() use ($correctionId, $data) {

            $correction = UserAttendanceService::reviewCorrection(
                $this->getCompanyId(),
                $correctionId,
                $this->getUserId(),
                (bool) $data["approve"],
                $data["note"] ?? null
            );

            return ["msg" => "Solicitud de corrección revisada.", "correction" => $correction];

        });

    }

    private function domainResponse(callable $callback): JsonResponse {

        try {

            return response()->json(["bool" => true, ...$callback()]);

        }catch(\DomainException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

    private function allowedBranchIds(): ?array {

        return CompanyReferenceDataService::forUser($this->getUserId())
            ->allowedBranchIds();

    }
}
