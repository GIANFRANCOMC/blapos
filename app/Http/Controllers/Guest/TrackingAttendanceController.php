<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\{Controller};
use App\Http\Requests\Guest\{PublicAttendanceRequest};
use App\Models\Guest\{Branch};
use App\Services\System\Customers\Tracking\{TrackingAttendanceBusinessService};
use Illuminate\Http\{Request};
use stdClass;

class TrackingAttendanceController extends Controller {
    public function initParams(Request $request) {

        $initParams = new stdClass();

        $initParams->bool = true;

        return $initParams;

    }

    public function index(Request $request) {

        abort(404, "Utiliza un enlace de asistencia vigente emitido por la empresa.");

    }

    public function signedIndex(Request $request, int $branch) {

        $company = $request->get("company");
        $record = Branch::query()
            ->where("status", "active")
            ->find($branch);

        abort_unless($record, 404, "La sucursal no está disponible.");

        $request->session()->put("_public_attendance_access", [
            "branch_id" => (int) $record->id,
            "expires_at" => (int) $request->query("expires", now()->addMinutes(15)->timestamp),
        ]);

        return view("Guest/general/tracking_attendances/main", [
            "company" => $company,
            "branch" => $record,
            "withMenu" => false,
            "publicAttendanceAccess" => [
                "branch_id" => (int) $record->id,
                "branch_name" => $record->name,
                "branch_address" => $record->address,
                "expires_at" => (int) $request->query("expires", now()->addMinutes(15)->timestamp),
            ],
            "meta" => [
                "title" => "Asistencia pública | {$company->commercial_name}",
                "description" => "Registro público de asistencia para la sucursal {$record->name}.",
                "image" => $company->combinationmark ?: $company->logotype ?: $company->logomark,
            ],
        ]);

    }

    public function qrCamera(PublicAttendanceRequest $request, TrackingAttendanceBusinessService $attendanceService) {

        $company = $request->get("company");

        $startDate = now();
        $endDate = now();

        $attendances = collect();

        foreach($request->validated("customers") as $customerRequest) {

            $result = $attendanceService->validateAndCreateAttendance([
                "branch_id" => $request->branch_id,
                "customer_id" => $customerRequest["customer_id"],
                "start_date" => $startDate,
                "end_date" => $endDate,
                "observation" => null,
                "user_id" => null,
                "type" => "qr_public",
                "action" => "automatic",
            ]);

            $attendances->push($result);

        }

        $bool = count($attendances->where("bool", true)) > 0;

        $msg = $bool ? "Asistencias creadas correctamente." : "No se han podido crear las asistencias.";

        return response()->json(["bool" => $bool, "msg" => $msg, "attendances" => $attendances], 200);

    }
}
