<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Organizations\Users\{ChangeUserPasswordRequest, RegisterUserFingerprintRequest, StoreUserRequest, UpdateUserRequest};
use App\Models\System\Organizations\{AuthenticationEvent};
use App\Services\System\Auth\{AuthenticationAuditService};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Devices\BiometricDevices\{BiometricDeviceService};
use App\Services\System\Organizations\Users\{UserConfigService, UserService};
use Illuminate\Http\{JsonResponse, Request};

class UserController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Organizations.user";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return UserConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return UserService::getPaginatedList($filters, $perPage);

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Organizations/users/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreUserRequest $request): JsonResponse {

        try {

            $data = $this->prepareUserData($request);
            $user = UserService::create($data, $this->getUserId());

            if(!Utilities::isDefined($user)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::USERS
            );

            return $this->createdResponse($user, "created", "user");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id User ID
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse {

        try {

            $user = UserService::findByIdInTenant($id, null);

            if(!Utilities::isDefined($user)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareUserData($request);

            $user = UserService::update($user, $data, $this->getUserId());

            if(!Utilities::isDefined($user)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::USERS
            );

            return $this->updatedResponse($user, "updated", "user");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    public function authenticationEvents(Request $request, int $id): JsonResponse {

        $user = UserService::findByIdInTenant($id, null, []);

        if(!$user) {

            return $this->notFoundResponse();

        }

        $events = AuthenticationEvent::query()
            ->where("user_id", $user->id)
            ->when($request->input("event_type"), fn($query, $eventType) => $query->where("event_type", $eventType))
            ->when($request->input("result"), fn($query, $result) => $query->where("result", $result))
            ->when($request->input("date_from"), fn($query, $date) => $query->where("occurred_at", ">=", Utilities::startOfDay($date)))
            ->when($request->input("date_to"), fn($query, $date) => $query->where("occurred_at", "<=", Utilities::endOfDay($date)))
            ->latest("occurred_at")
            ->paginate($this->getPerPage($request));

        return response()->json([
            "bool" => true,
            "data" => $events,
        ]);

    }

    public function changePassword(ChangeUserPasswordRequest $request, int $id): JsonResponse {

        try {

            $user = UserService::findByIdInTenant($id, null, []);

            if(!$user) {

                return $this->notFoundResponse();

            }

            $user = UserService::changePassword(
                $user,
                $request->password,
                $this->getUserId()
            );

            AuthenticationAuditService::record(
                $request,
                "password_changed",
                "success",
                $user,
                $user->email,
                "Contraseña actualizada desde Colaboradores."
            );

            return $this->updatedResponse($user, "updated", "user");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    public function registerBiometricFingerprint(RegisterUserFingerprintRequest $request, int $id): JsonResponse {

        $data = $request->validated();

        try {

            $user = UserService::findByIdInTenant($id, ["active"]);

            if(!$user) {

                return $this->notFoundResponse();

            }

            $deviceId = (int) $data["biometric_device_id"];

            $device = BiometricDeviceService::findByIdInTenant($deviceId, ["active"]);

            if(!$device) {

                return $this->errorResponse("not_found", ["msg" => "El dispositivo biométrico no está disponible."], 404);

            }

            $deviceUserId = isset($data["device_user_id"])
                ? (int) $data["device_user_id"]
                : BiometricDeviceService::getNextDeviceUserId($deviceId);

            $fingerprint = BiometricDeviceService::registerUserFingerprint(
                (int) $user->id,
                $deviceId,
                $deviceUserId,
                (int) ($data["finger_index"] ?? 0),
                $this->getUserId()
            );

            return $this->createdResponse($fingerprint, "fingerprint_registered", "biometric_fingerprint");

        }catch(\DomainException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }catch(\Throwable $exception) {

            return $this->handleException($exception, "register_fingerprint");

        }

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreUserRequest|UpdateUserRequest  $request
     */
    private function prepareUserData($request): array {

        $data = [
            "role_id" => $request->input("role_id"),
            "identity_document_type_id" => $request->input("identity_document_type_id"),
            "document_number" => $request->input("document_number"),
            "name" => $request->input("name"),
            "email" => $request->input("email"),
            "phone_number" => $request->input("phone_number"),
            "gender" => $request->input("gender"),
            "birthdate" => $request->input("birthdate"),
            "status" => $request->input("status"),
            "branch_ids" => collect($request->input("branch_ids", []))
                ->filter()
                ->map(fn($branchId) => (int) $branchId)
                ->values()
                ->all(),
            "cash_register_ids" => collect($request->input("cash_register_ids", []))
                ->filter()
                ->map(fn($cashRegisterId) => (int) $cashRegisterId)
                ->values()
                ->all(),
            "warehouse_ids" => collect($request->input("warehouse_ids", []))
                ->filter()
                ->map(fn($warehouseId) => (int) $warehouseId)
                ->values()
                ->all(),
        ];

        if($request instanceof StoreUserRequest) {

            $data["password"] = $request->input("password");

        }

        $data["branch_scope_mode"] = empty($data["branch_ids"]) ? "inherit" : "restricted";

        $data["cash_register_scope_mode"] = empty($data["cash_register_ids"]) ? "inherit" : "restricted";
        $data["warehouse_scope_mode"] = empty($data["warehouse_ids"]) ? "inherit" : "restricted";

        return $data;

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
