<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Helpers\System\{Utilities};
use App\Models\System\Customers\{Attendance, Customer, Subscription};
use App\Services\System\Devices\BiometricDevices\{BiometricDeviceService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use Carbon\{Carbon};
use Illuminate\Support\Facades\{DB};

/**
 * Business Service for Attendance Operations
 * Handles complex business logic for attendance validation and creation
 */
class TrackingAttendanceBusinessService {

    /**
     * Validate start date format
     */
    public function validateStartDate(?Carbon $startDate): bool {

        return Utilities::isDefined($startDate) &&
               Utilities::isValidDateFormat($startDate->format("Y-m-d H:i:s"), "Y-m-d H:i:s");

    }

    /**
     * Get valid customer by code or document number
     *
     * @param  string|int  $code Customer ID or document number
     * @param  int  $companyId Company ID
     * @param  string  $type Search type: "document_number" or "carnet"
     */
    public function getValidCustomer($code, string $type = ""): ?Customer {

        if($this->normalizeLookupType($type) === "document_number") {

            return Customer::where("document_number", $code)
                ->first();

        }

        return Customer::where("id", $code)
            ->first();

    }

    /**
     * Get valid active subscriptions for customer
     *
     * @param  int  $companyId Company ID
     * @param  int  $branchId Branch ID
     * @param  int  $customerId Customer ID
     * @param  Carbon  $startDate Start date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getValidSubscriptions(int $branchId, int $customerId, Carbon $startDate) {

        return Subscription::query()
            ->where("branch_id", $branchId)
            ->where("customer_id", $customerId)
            ->where("start_date", "<=", $startDate)
            ->where("end_date", ">=", $startDate)
            ->where("status", "active")
            ->orderBy("attendance_limit_per_day", "DESC")
            ->get();

    }

    /**
     * Check attendance limits for customer
     *
     * @param  int  $companyId Company ID
     * @param  int  $branchId Branch ID
     * @param  int  $customerId Customer ID
     * @param  Carbon  $startDate Start date
     * @param  int  $limit Daily limit
     */
    public function checkAttendanceLimits(int $branchId, int $customerId, Carbon $startDate, int $limit): array {

        $dailyAttendances = Attendance::query()
            ->where("customer_id", $customerId)
            ->whereBetween("start_date", [
                $startDate->copy()->startOfDay(),
                $startDate->copy()->endOfDay(),
            ]);

        $scope = (string) CompanySettingService::value(
            CompanySettingService::CUSTOMER_ATTENDANCE,
            "daily_limit_scope",
            "branch"
        );

        if($scope !== "company") {

            $dailyAttendances->where("branch_id", $branchId);

        }

        $hasActive = (clone $dailyAttendances)
            ->where("status", "active")
            ->exists();

        $finalized = (clone $dailyAttendances)
            ->where("status", "finalized")
            ->count();

        return [
            "hasActive" => $hasActive,
            "exceedsLimit" => $finalized >= $limit,
        ];

    }

    /**
     * Create new attendance record
     *
     * @param  array  $data Attendance data
     */
    public function createAttendance(array $data): Attendance {

        return Attendance::create([
            "branch_id" => $data["branch_id"],
            "customer_id" => $data["customer_id"],
            "biometric_device_id" => $data["biometric_device_id"] ?? null,
            "source_reference" => $data["source_reference"] ?? null,
            "start_date" => $data["start_date"],
            "end_date" => $data["end_date"],
            "observation" => $data["observation"],
            "type" => $data["type"],
            "status" => "active",
            "created_at" => now(),
            "created_by" => $data["user_id"],
        ]);

    }

    /**
     * Validate and create attendance with full business logic
     *
     * @param  array  $data Attendance data
     * @return array Response array with bool, msg, and optional data
     */
    public function validateAndCreateAttendance(array $data): array {

        return DB::transaction(
            fn() => $this->validateAndCreateAttendanceWithinTransaction($data)
        );

    }

    private function validateAndCreateAttendanceWithinTransaction(array $data): array {

        $response = [
            "bool" => false,
            "msg" => "",
        ];

        $branchId = $data["branch_id"];
        $customerId = $data["customer_id"] ?? "";
        $customerDocumentNumber = $data["customer_document_number"] ?? "";
        $customerAttendanceType = Utilities::isDefined($data["customer_attendance_type"] ?? "")
            ? $this->normalizeLookupType((string) $data["customer_attendance_type"])
            : "carnet";

        $startDate = $data["start_date"] ?? null;
        $endDate = $data["end_date"] ?? null;
        $observation = $data["observation"] ?? "";
        $userId = $data["user_id"] ?? null;
        $type = $data["type"] ?? "manual_form";
        $action = $data["action"] ?? "automatic";

        if($action === "checkout"
            && in_array($type, ["biometric", "qr_camera", "qr_scanner", "qr_public"], true)
            && !(bool) CompanySettingService::value(
                CompanySettingService::CUSTOMER_ATTENDANCE,
                "allow_automatic_checkout",
                false
            )) {

            return [
                "bool" => false,
                "msg" => "La salida automática por QR o biometría no está habilitada para la empresa.",
            ];

        }

        // For automatic checkin actions, use current time if start_date is not provided

        if($startDate === null && $action === "automatic" && in_array($type, ["biometric", "qr_camera", "qr_scanner", "qr_public"])) {

            $startDate = now();

        }

        if($endDate === null && $action === "checkout" && in_array($type, ["biometric", "qr_camera", "qr_scanner", "qr_public"], true)) {

            $endDate = now();

        }

        // Get customer
        // Handle biometric attendance (by device_user_id)
        $deviceId = $data["device_id"] ?? null;

        $deviceUserId = $data["device_user_id"] ?? null;
        $sourceReference = $data["source_reference"] ?? null;

        if(Utilities::isDefined($deviceId) && Utilities::isDefined($deviceUserId) && $type === "biometric") {

            $customer = BiometricDeviceService::findCustomerByDeviceUserId(
                $deviceId,
                $deviceUserId
            );

        }elseif($customerAttendanceType === "document_number") {

            $customer = $this->getValidCustomer($customerDocumentNumber, $customerAttendanceType);

        }else {

            $customer = $this->getValidCustomer($customerId, $customerAttendanceType);

        }

        if(!Utilities::isDefined($customer)) {

            $response["msg"] = "No se ha encontrado el cliente solicitado.";

            return $response;

        }

        $customer = Customer::query()
            ->lockForUpdate()
            ->find($customer->id);

        if(!Utilities::isDefined($customer)) {

            $response["msg"] = "No se ha encontrado el cliente solicitado.";

            return $response;

        }

        $response["customer"] = $customer;

        if($type === "biometric" && Utilities::isDefined($deviceId)) {

            $tolerance = max(0, (int) CompanySettingService::value(
                CompanySettingService::CUSTOMER_ATTENDANCE,
                "biometric_duplicate_tolerance_seconds",
                10
            ));

            $duplicate = Attendance::query()
                ->where("customer_id", $customer->id)
                ->where("biometric_device_id", $deviceId)
                ->where("created_at", ">=", now()->subSeconds($tolerance))
                ->exists();

            if($tolerance > 0 && $duplicate) {

                return [
                    "bool" => true,
                    "msg" => "Lectura biométrica duplicada ignorada.",
                    "action" => "ignored_duplicate",
                    "customer" => $customer,
                ];

            }

        }

        // Check for active attendance
        $activeAttendanceQuery = Attendance::query()
            ->where("branch_id", $branchId)
            ->where("customer_id", $customer->id)
            ->where("status", "active");

        if(Utilities::isDefined($data["attendance_id"] ?? null)) {

            $activeAttendanceQuery->whereKey((int) $data["attendance_id"]);

        }

        $activeAttendance = $activeAttendanceQuery
            ->lockForUpdate()
            ->latest("start_date")
            ->first();

        $maxActiveHours = $this->maxActiveHours();

        // Handle checkout

        if(in_array($action, ["checkout"])) {

            if(!Utilities::isDefined($activeAttendance)) {

                $response["msg"] = "$customer->name: No se ha podido concluir la asistencia.";

                return $response;

            }

            $proposedStartDate = Carbon::parse($activeAttendance->start_date);

            $proposedEndDate = $endDate;

            if(!$proposedEndDate->greaterThan($proposedStartDate)) {

                $response["msg"] = "$customer->name: La salida debe ser mayor al ingreso ".$proposedStartDate->format("d-m-Y h:i A").".";

                return $response;

            }

            if($proposedEndDate->diffInMinutes($proposedStartDate) < 2) {

                $response["msg"] = "$customer->name: La salida debe ser al menos 2 minutos después del ingreso ".$proposedStartDate->format("d-m-Y h:i A").".";

                return $response;

            }

            if($this->attendanceExceedsMaxDuration($activeAttendance, $proposedEndDate, $maxActiveHours)) {

                $response["msg"] = "$customer->name: La asistencia supera {$maxActiveHours} horas. Registra una corrección o crea una nueva asistencia.";

                return $response;

            }

            $activeAttendance->end_date = $proposedEndDate;

            $activeAttendance->status = "finalized";
            $activeAttendance->updated_at = now();
            $activeAttendance->updated_by = $userId;
            $activeAttendance->save();

            $response["bool"] = true;
            $response["msg"] = "¡Hasta pronto, $customer->name! Gracias por visitarnos.";
            $response["action"] = "checkout";

            return $response;

        }

        // Break: Checkout action without active attendance

        if(in_array($action, ["checkout"])) {

            $response["msg"] = "$customer->name: Sin respuesta.";

            return $response;

        }

        // Check for active attendance (checkin)

        if(Utilities::isDefined($activeAttendance)) {

            if($this->attendanceExceedsMaxDuration($activeAttendance, $startDate, $maxActiveHours)) {

                $this->closeExpiredAttendance($activeAttendance, $maxActiveHours, $userId);
                $activeAttendance = null;

            }

        }

        if(Utilities::isDefined($activeAttendance)) {

            $response["msg"] = "$customer->name: Cuenta con un registro de asistencia 'En curso'.";

            return $response;

        }

        // Validate subscriptions
        $subscriptions = $this->getValidSubscriptions($branchId, $customer->id, $startDate);

        if($subscriptions->isEmpty()) {

            $response["msg"] = "$customer->name: No cuenta con una membresía vigente en la sucursal.";

            return $response;

        }

        $subscription = $subscriptions->first();

        $limitPerDay = intval($subscription->attendance_limit_per_day);

        // Check attendance limits
        $check = $this->checkAttendanceLimits($branchId, $customer->id, $startDate, $limitPerDay);

        if($check["hasActive"]) {

            $response["msg"] = "$customer->name: Cuenta con un registro de asistencia 'En curso'.";

            return $response;

        }

        if($check["exceedsLimit"]) {

            $response["msg"] = "$customer->name: Alcanzó el límite diario de {$limitPerDay} asistencia(s) para esta sucursal.";

            return $response;

        }

        // Create attendance
        $result = $this->createAttendance([
            "branch_id" => $branchId,
            "customer_id" => $customer->id,
            "biometric_device_id" => $deviceId,
            "source_reference" => $sourceReference,
            "start_date" => $startDate,
            "end_date" => null,
            "observation" => $observation,
            "user_id" => $userId,
            "type" => $type,
        ]);

        $response["bool"] = true;
        $response["msg"] = "¡Bienvenido, $customer->name! Disfruta tu rutina.";
        $response["action"] = "checkin";

        return $response;

    }

    private function normalizeLookupType(string $type): string {

        return in_array($type, ["dni", "dnie", "document_number"], true)
            ? "document_number"
            : $type;

    }

    private function maxActiveHours(): int {

        return max(1, (int) CompanySettingService::value(
            CompanySettingService::CUSTOMER_ATTENDANCE,
            "max_active_hours",
            20
        ));

    }

    private function attendanceExceedsMaxDuration(Attendance $attendance, Carbon $endDate, int $maxHours): bool {

        $startDate = Carbon::parse($attendance->start_date);

        return $endDate->diffInMinutes($startDate) > ($maxHours * 60);

    }

    private function closeExpiredAttendance(Attendance $attendance, int $maxHours, ?int $userId): void {

        $endDate = Carbon::parse($attendance->start_date)->addHours($maxHours);
        $note = "Finalizada automáticamente por superar {$maxHours} horas sin salida.";
        $currentObservation = trim((string) $attendance->observation);

        $attendance->end_date = $endDate;
        $attendance->status = "finalized";
        $attendance->observation = trim($currentObservation === "" ? $note : "{$currentObservation}\n{$note}");
        $attendance->updated_at = now();
        $attendance->updated_by = $userId;
        $attendance->save();

    }
}
