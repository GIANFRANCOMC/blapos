<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Operations;

use App\Helpers\System\{ApiResponse};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Operations\{AddServiceSessionItemRequest, CancelServiceSessionRequest, OpenServiceSessionRequest, PauseServiceSessionRequest, ReassignServiceSessionRequest, StoreServiceFloorRequest, StoreServiceStationRequest, UpdatePreparationStatusRequest, UpdateServiceStationLayoutRequest};
use App\Services\System\Operations\{ServiceOperationConfigService, ServiceOperationService};
use Illuminate\Http\{JsonResponse, Request};
use Throwable;

final class ServiceOperationController extends BaseController {
    public function index() {

        return view("System/general/Operations/service_operations/main");

    }

    public function initParams(Request $request): JsonResponse {

        return response()->json(
            ServiceOperationConfigService::getInitParams(
                (string) $request->input("page", "restaurant"),
                $this->getUserId()
            )
        );

    }

    public function stations(Request $request): JsonResponse {

        $request->validate(["branch_id" => ["required", "integer"]]);

        return response()->json([
            "bool" => true,
            "data" => ServiceOperationService::stations(
                $this->getUserId(),
                (int) $request->input("branch_id"),
                $request->filled("service_floor_id") ? (int) $request->input("service_floor_id") : null
            ),
        ]);

    }

    public function board(Request $request): JsonResponse {

        $data = $request->validate([
            "branch_id" => ["required", "integer"],
            "service_floor_id" => ["nullable", "integer"],
        ]);

        return response()->json([
            "bool" => true,
            "data" => ServiceOperationService::board(
                $this->getUserId(),
                (int) $data["branch_id"],
                isset($data["service_floor_id"]) ? (int) $data["service_floor_id"] : null
            ),
        ]);

    }

    public function options(Request $request): JsonResponse {

        $data = $request->validate([
            "resource" => ["required", "in:customers,items"],
            "search" => ["nullable", "string", "max:100"],
            "item_type" => ["nullable", "in:product,service,subscription"],
        ]);

        return response()->json([
            "bool" => true,
            "data" => ServiceOperationService::options(
                (string) $data["resource"],
                trim((string) ($data["search"] ?? "")),
                $data["item_type"] ?? null
            ),
        ]);

    }

    public function floors(Request $request): JsonResponse {

        $request->validate(["branch_id" => ["required", "integer"]]);

        return response()->json([
            "bool" => true,
            "data" => ServiceOperationService::floors(
                $this->getUserId(),
                (int) $request->input("branch_id")
            ),
        ]);

    }

    public function storeFloor(StoreServiceFloorRequest $request): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::createFloor(
                $this->getUserId(),
                $request->validated()
            ),
            "Piso registrado correctamente."
        );

    }

    public function updateFloor(StoreServiceFloorRequest $request, int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::updateFloor(
                $this->getUserId(),
                $id,
                $request->validated()
            ),
            "Piso actualizado correctamente."
        );

    }

    public function sessions(Request $request): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => ServiceOperationService::sessions(
                $this->getUserId(),
                $request->only([
                    "branch_id",
                    "service_station_id",
                    "assigned_user_id",
                    "status",
                    "session_type",
                    "date_from",
                    "date_to",
                ]),
                $this->getPerPage($request)
            ),
        ]);

    }

    public function reports(Request $request): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => ServiceOperationService::reports(
                $this->getUserId(),
                $request->only([
                    "branch_id",
                    "service_station_id",
                    "assigned_user_id",
                    "session_type",
                    "date_from",
                    "date_to",
                ])
            ),
        ]);

    }

    public function show(int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::find($id, $this->getUserId())
        );

    }

    public function storeStation(StoreServiceStationRequest $request): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::createStation(
                $this->getUserId(),
                $request->validated()
            ),
            "Estación registrada correctamente."
        );

    }

    public function updateStation(StoreServiceStationRequest $request, int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::updateStation(
                $this->getUserId(),
                $id,
                $request->validated()
            ),
            "Mesa actualizada correctamente."
        );

    }

    public function updateStationLayout(UpdateServiceStationLayoutRequest $request, int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::updateStationLayout(
                $this->getUserId(),
                $id,
                $request->validated()
            ),
            "Distribución actualizada correctamente."
        );

    }

    public function openSession(OpenServiceSessionRequest $request): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::open(
                $this->getUserId(),
                $request->validated()
            ),
            "Atención iniciada correctamente."
        );

    }

    public function addItem(AddServiceSessionItemRequest $request, int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::addItem(
                $this->getUserId(),
                $id,
                $request->validated()
            ),
            "Detalle agregado a la atención."
        );

    }

    public function startSession(int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::start($this->getUserId(), $id),
            "Servicio iniciado."
        );

    }

    public function completeSession(int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::complete($this->getUserId(), $id),
            "Servicio finalizado correctamente."
        );

    }

    public function startItem(int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::startItem($this->getUserId(), $id),
            "Detalle iniciado."
        );

    }

    public function completeItem(int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::completeItem($this->getUserId(), $id),
            "Detalle finalizado."
        );

    }

    public function updatePreparationStatus(UpdatePreparationStatusRequest $request, int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::updatePreparationStatus(
                $this->getUserId(),
                $id,
                (string) $request->validated("status")
            ),
            "Estado de preparación actualizado."
        );

    }

    public function reassignSession(ReassignServiceSessionRequest $request, int $id): JsonResponse {

        $data = $request->validated();

        return $this->execute(
            fn() => ServiceOperationService::reassign(
                $this->getUserId(),
                $id,
                (int) $data["assigned_user_id"],
                $data["note"] ?? null
            ),
            "Responsable actualizado correctamente."
        );

    }

    public function pauseSession(PauseServiceSessionRequest $request, int $id): JsonResponse {

        $data = $request->validated();

        return $this->execute(
            fn() => ServiceOperationService::pause(
                $this->getUserId(),
                $id,
                isset($data["service_session_item_id"]) ? (int) $data["service_session_item_id"] : null,
                $data["reason"] ?? null
            ),
            "Pausa registrada correctamente."
        );

    }

    public function resumeSession(int $id): JsonResponse {

        return $this->execute(
            fn() => ServiceOperationService::resume($this->getUserId(), $id),
            "Atención reanudada correctamente."
        );

    }

    public function cancelSession(CancelServiceSessionRequest $request, int $id): JsonResponse {

        $data = $request->validated();

        return $this->execute(
            fn() => ServiceOperationService::cancel(
                $this->getUserId(),
                $id,
                $data["reason"]
            ),
            "Atención cancelada correctamente."
        );

    }

    private function execute(callable $callback, string $message = "Operación completada."): JsonResponse {

        try {

            return ApiResponse::success($callback(), $message);

        }catch(Throwable $exception) {

            return ApiResponse::error($exception->getMessage(), 422);

        }

    }

    protected function getTranslationNamespace(): string {

        return "System.Operations.service_operation";

    }
}
