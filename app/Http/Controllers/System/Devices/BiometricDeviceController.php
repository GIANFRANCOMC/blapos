<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Devices;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Devices\BiometricDevices\{StoreBiometricDeviceRequest, UpdateBiometricDeviceRequest};
use App\Models\System\Devices\{BiometricDeviceModel};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Customers\Tracking\{TrackingAttendanceBusinessService};
use App\Services\System\Devices\BiometricDevices\{BiometricDeviceConfigService, BiometricDeviceService};
use Illuminate\Http\{JsonResponse, Request};

class BiometricDeviceController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Devices.biometric_device";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return BiometricDeviceConfigService::getInitParams($this->getCompanyId(), $page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return BiometricDeviceService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Devices/biometric_devices/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreBiometricDeviceRequest $request): JsonResponse {

        try {

            $data = $this->prepareBiometricDeviceData($request);
            $device = BiometricDeviceService::create($data, $this->getCompanyId(), $this->getUserId());

            if(!Utilities::isDefined($device)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BIOMETRIC_DEVICES,
                $this->getCompanyId()
            );

            return $this->createdResponse($device, "created", "biometric_device");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Biometric Device
     */
    public function update(UpdateBiometricDeviceRequest $request, int $id): JsonResponse {

        try {

            $device = BiometricDeviceService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($device)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareBiometricDeviceData($request);

            $device = BiometricDeviceService::update($device, $data, $this->getUserId());

            if(!Utilities::isDefined($device)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BIOMETRIC_DEVICES,
                $this->getCompanyId()
            );

            return $this->updatedResponse($device, "updated", "biometric_device");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    public function rotateCredentials(int $id): JsonResponse {

        try {

            $device = BiometricDeviceService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!$device) {

                return $this->notFoundResponse();

            }

            return response()->json([
                "bool" => true,
                "msg" => "Credenciales rotadas correctamente. Guarda el secreto porque no volverá a mostrarse.",
                "data" => BiometricDeviceService::rotateCredentials($device, $this->getUserId()),
            ]);

        }catch(\Throwable $exception) {

            return $this->handleException($exception, "update");

        }

    }

    public function events(Request $request, int $id): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => BiometricDeviceService::getDeviceEvents(
                $this->getCompanyId(),
                $id,
                [
                    "processing_status" => $request->input("processing_status"),
                    "event_type" => $request->input("event_type"),
                ],
                $this->getPerPage($request, Utilities::$per_page_default)
            ),
        ]);

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreBiometricDeviceRequest|UpdateBiometricDeviceRequest  $request
     * @return array
     */
    private function resolveBiometricDeviceModelId($request): ?int {

        $modelId = $request->input("biometric_device_model_id");

        if(Utilities::isDefined($modelId)) {

            return (int) $modelId;

        }

        $modelName = $request->input("model");

        if(!Utilities::isDefined($modelName)) {

            return null;

        }

        $query = BiometricDeviceModel::query()
            ->where("status", "active")
            ->where("name", $modelName);

        if(Utilities::isDefined($request->input("brand"))) {

            $query->whereHas("brand", fn($brandQuery) => $brandQuery->where("name", $request->input("brand")));

        }

        return $query->value("id");

    }

    private function prepareBiometricDeviceData($request): array {

        return [
            "branch_id" => $request->input("branch_id"),
            "biometric_device_model_id" => $this->resolveBiometricDeviceModelId($request),
            "name" => $request->input("name"),
            "serial_number" => $request->input("serial_number"),
            "ip_address" => $request->input("ip_address"),
            "port" => $request->input("port"),
            "device_id" => $request->input("device_id"),
            "description" => $request->input("description"),
            "status" => $request->input("status", "active"),
        ];

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
