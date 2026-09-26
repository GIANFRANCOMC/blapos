<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Customers;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Customers\TrackingAttendances\{CancelTrackingAttendanceRequest, CheckoutTrackingAttendanceRequest, ProcessTrackingAttendanceBatchRequest, ReviewAttendanceCorrectionRequest, StoreAttendanceCorrectionRequest, StoreTrackingAttendanceRequest};
use App\Models\System\Customers\{Attendance, AttendanceCorrection};
use App\Services\System\Customers\Tracking\{TrackingAttendanceBusinessService, TrackingAttendanceConfigService, TrackingAttendanceService};
use App\Services\System\Organizations\{AccessScopeService};
use Carbon\{Carbon};
use DomainException;
use Illuminate\Http\{JsonResponse, Request, Response};

class TrackingAttendanceController extends BaseController {
    /**
     * Translation namespace for tracking attendance module
     */
    private const TRANSLATION_NAMESPACE = "System.Customers.tracking_attendance";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return TrackingAttendanceConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list of attendances with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $perPage = $this->getPerPage($request, Utilities::$per_page_max);

        return TrackingAttendanceService::getPaginatedList(
            $this->filters($request),
            $perPage,
            AccessScopeService::allowedIds($this->getAuthUser(), AccessScopeService::BRANCH)
        );

    }

    public function export(Request $request): Response|JsonResponse {

        try {

            $records = TrackingAttendanceService::getForExport(
                $this->filters($request),
                AccessScopeService::allowedIds($this->getAuthUser(), AccessScopeService::BRANCH)
            );

        }catch(DomainException $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }
        $handle = fopen("php://temp", "r+");

        fputcsv($handle, [
            "Inicio",
            "Fin",
            "Sucursal",
            "Cliente",
            "Estado",
            "Origen",
            "Dispositivo biométrico",
            "Observación",
        ], ";");

        foreach($records as $record) {

            fputcsv($handle, [
                $record->start_date,
                $record->end_date,
                $record->branch?->name,
                $record->customer?->name,
                $record->formatted_status,
                $record->type,
                $record->biometricDevice?->name,
                $record->observation,
            ], ";");

        }

        rewind($handle);

        $csv = stream_get_contents($handle);
        fclose($handle);

        return response("\xEF\xBB\xBF".$csv, 200, [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=asistencias-clientes-".now()->format("Ymd-His").".csv",
        ]);

    }

    /**
     * Display the tracking attendances index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Customers/tracking_attendances/main");

    }

    public function store(StoreTrackingAttendanceRequest $request, TrackingAttendanceBusinessService $businessService): JsonResponse {

        try {

            $data = $request->validated();

            if(!AccessScopeService::canAccess(
                $this->getAuthUser(),
                AccessScopeService::BRANCH,
                (int) $data["branch_id"]
            )) {

                return $this->errorResponse("unauthorized", [], 403);

            }

            $startDate = Utilities::isDefined($data["start_date"] ?? null)
                ? Carbon::parse(str_replace("T", " ", $data["start_date"]))
                : now();

            $endDate = Utilities::isDefined($data["end_date"] ?? null)
                ? Carbon::parse(str_replace("T", " ", $data["end_date"]))
                : now();

            $result = $businessService->validateAndCreateAttendance([
                "branch_id" => (int) $data["branch_id"],
                "customer_id" => (int) $data["customer_id"],
                "start_date" => $startDate,
                "end_date" => $endDate,
                "observation" => $data["observation"] ?? null,
                "user_id" => $this->getUserId(),
                "type" => "manual_form",
                "action" => "automatic",
            ]);

            if($result["bool"]) {

                return $this->successResponse(
                    ["attendances" => [$result]],
                    "created"
                );

            }

            return response()->json([
                "bool" => false,
                "msg" => $result["msg"] ?? "No fue posible registrar la asistencia.",
                "attendances" => [$result],
            ], 422);

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    public function update(CheckoutTrackingAttendanceRequest $request, int $id, TrackingAttendanceBusinessService $businessService): JsonResponse {

        try {

            $attendance = Attendance::query()
                ->where("status", "active")
                ->find($id);

            if(!$attendance
                || !AccessScopeService::canAccess(
                    $this->getAuthUser(),
                    AccessScopeService::BRANCH,
                    (int) $attendance->branch_id
                )) {

                return $this->notFoundResponse();

            }

            $data = $request->validated();

            $endDate = Utilities::isDefined($data["end_date"] ?? null)
                ? Carbon::parse(str_replace("T", " ", $data["end_date"]))
                : now();

            $result = $businessService->validateAndCreateAttendance([
                "attendance_id" => (int) $attendance->id,
                "branch_id" => (int) $attendance->branch_id,
                "customer_id" => (int) $attendance->customer_id,
                "start_date" => null,
                "end_date" => $endDate,
                "observation" => null,
                "user_id" => $this->getUserId(),
                "action" => "checkout",
            ]);

            if($result["bool"]) {

                return $this->successResponse(
                    ["attendances" => [$result]],
                    "updated"
                );

            }

            return response()->json([
                "bool" => false,
                "msg" => $result["msg"] ?? "No fue posible registrar la salida.",
                "attendances" => [$result],
            ], 422);

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Cancel the specified attendance
     *
     * @param  int  $id Attendance ID
     */
    public function cancel(CancelTrackingAttendanceRequest $request, int $id): JsonResponse {

        try {

            $attendance = Attendance::query()
                ->find($id);

            if(!$attendance
                || !AccessScopeService::canAccess(
                    $this->getAuthUser(),
                    AccessScopeService::BRANCH,
                    (int) $attendance->branch_id
                )) {

                return $this->notFoundResponse();

            }

            $attendance = TrackingAttendanceService::cancel($attendance, $request->motive, $this->getUserId());

            return $this->updatedResponse($attendance, "canceled", "attendance");

        }catch(\Exception $e) {

            return $this->handleException($e, "cancel");

        }

    }

    /**
     * Get translation namespace for tracking attendance module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

    public function requestCorrection(StoreAttendanceCorrectionRequest $request, int $id): JsonResponse {

        try {

            $attendance = Attendance::query()
                ->find($id);

            if(!$attendance
                || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::BRANCH, (int) $attendance->branch_id)) {

                return $this->notFoundResponse();

            }

            $correction = TrackingAttendanceService::requestCorrection(
                $attendance,
                $request->validated(),
                $this->getUserId()
            );

            return $this->createdResponse($correction, "created", "attendanceCorrection");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    public function reviewCorrection(ReviewAttendanceCorrectionRequest $request, int $id): JsonResponse {

        try {

            $correction = AttendanceCorrection::query()
                ->with("attendance")
                ->find($id);

            if(!$correction
                || !$correction->attendance
                || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::BRANCH, (int) $correction->attendance->branch_id)) {

                return $this->notFoundResponse();

            }

            $correction = TrackingAttendanceService::reviewCorrection(
                $correction,
                $request->decision,
                $request->note,
                $this->getUserId()
            );

            return $this->updatedResponse($correction, "updated", "attendanceCorrection");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    public function qrCamera(ProcessTrackingAttendanceBatchRequest $request, TrackingAttendanceBusinessService $businessService): JsonResponse {

        try {

            return $this->processBatch($request->validated(), "qr_camera", $businessService);

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    public function qrScanner(ProcessTrackingAttendanceBatchRequest $request, TrackingAttendanceBusinessService $businessService): JsonResponse {

        try {

            return $this->processBatch($request->validated(), "qr_scanner", $businessService);

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    private function processBatch(
        array $data,
        string $type,
        TrackingAttendanceBusinessService $businessService
    ): JsonResponse {

        $branchId = (int) $data["branch_id"];

        if(!AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::BRANCH, $branchId)) {

            return $this->errorResponse("unauthorized", [], 403);

        }

        $startDate = Utilities::isDefined($data["start_date"] ?? null)
            ? Carbon::parse(str_replace("T", " ", $data["start_date"]))
            : now();

        $endDate = Utilities::isDefined($data["end_date"] ?? null)
            ? Carbon::parse(str_replace("T", " ", $data["end_date"]))
            : now();

        $attendances = collect($data["customers"])->map(function(array $customer) use (
            $businessService,
            $branchId,
            $startDate,
            $endDate,
            $data,
            $type
        ) {

            return $businessService->validateAndCreateAttendance([
                "branch_id" => $branchId,
                "customer_id" => $customer["customer_id"] ?? "",
                "customer_document_number" => $customer["customer_document_number"] ?? "",
                "customer_attendance_type" => $customer["customer_attendance_type"] ?? "",
                "start_date" => $startDate,
                "end_date" => $endDate,
                "observation" => $data["observation"] ?? null,
                "user_id" => $this->getUserId(),
                "type" => $type,
                "action" => "automatic",
            ]);

        });

        if($attendances->contains("bool", true)) {

            return $this->successResponse(["attendances" => $attendances->all()], "created");

        }

        return $this->errorResponse("create_failed", [], 422);

    }

    private function filters(Request $request): array {

        return $request->only(["branch_id", "customer_id", "status", "start_date", "end_date"]);

    }
}
