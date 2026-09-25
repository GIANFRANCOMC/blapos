<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Customers;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Customers\Customers\{RegisterCustomerFingerprintRequest, StoreCustomerRequest, UpdateCustomerRequest};
use App\Models\System\Customers\{Subscription};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Customers\Customers\{CustomerConfigService, CustomerService};
use App\Services\System\Devices\BiometricDevices\{BiometricDeviceService};
use Illuminate\Http\{JsonResponse, Request};

class CustomerController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Customers.customer";

    /**
     * Get initialization parameters for the module
     *
     * @param  RegisterCustomerFingerprintRequest  $request
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return CustomerConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return CustomerService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Customers/customers/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreCustomerRequest $request): JsonResponse {

        try {

            $data = $this->prepareCustomerData($request);
            $customer = CustomerService::create($data, $this->getCompanyId(), $this->getUserId());

            if(!Utilities::isDefined($customer)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::CUSTOMERS,
                $this->getCompanyId()
            );

            return $this->createdResponse($customer, "created", "customer");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Customer ID
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse {

        try {

            $data = $request->validated();
            $customer = CustomerService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($customer)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareCustomerData($request);

            $customer = CustomerService::update($customer, $data, $this->getUserId());

            if(!Utilities::isDefined($customer)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::CUSTOMERS,
                $this->getCompanyId()
            );

            return $this->updatedResponse($customer, "updated", "customer");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreCustomerRequest|UpdateCustomerRequest  $request
     */
    private function prepareCustomerData($request): array {

        return [
            "identity_document_type_id" => $request->identity_document_type_id,
            "document_number" => $request->document_number,
            "name" => $request->name,
            "email" => $request->email,
            "phone_number" => $request->phone_number,
            "emergency_contact_name" => $request->emergency_contact_name,
            "emergency_contact_phone" => $request->emergency_contact_phone,
            "medical_notes" => $request->medical_notes,
            "gender" => $request->gender ?? "other",
            "birthdate" => $request->birthdate,
            "status" => $request->status,
        ];

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

    /**
     * Get subscriptions for a customer
     *
     * @param  int  $id Customer ID
     */
    public function getSubscriptions(int $id): JsonResponse {

        try {

            $customer = CustomerService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($customer)) {

                return $this->errorResponse("not_found", [], 404);

            }

            $subscriptions = Subscription::query()
                ->where("customer_id", $customer->id)
                ->whereIn("status", ["active"])
                ->get();

            return $this->successResponse(["subscriptions" => $subscriptions], "retrieved");

        }catch(\Exception $e) {

            return $this->handleException($e, "retrieve");

        }

    }

    /**
     * Register biometric fingerprint for customer
     *
     * @param  Request  $request
     * @param  int  $id Customer ID
     */
    public function registerBiometricFingerprint(RegisterCustomerFingerprintRequest $request, int $id): JsonResponse {

        try {

            $customer = CustomerService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($customer)) {

                return $this->notFoundResponse();

            }

            $biometricDeviceId = (int) $data["biometric_device_id"];

            $deviceUserId = $data["device_user_id"] ?? null;
            $fingerIndex = (int) ($data["finger_index"] ?? 0);

            if(!Utilities::isDefined($deviceUserId)) {

                $deviceUserId = BiometricDeviceService::getNextDeviceUserId($biometricDeviceId);

            }else {

                if(BiometricDeviceService::deviceUserIdExists($biometricDeviceId, (int) $deviceUserId, $fingerIndex)) {

                    return $this->errorResponse("validation_error", ["msg" => "Ya existe una huella registrada para este usuario y dedo en el dispositivo."], 422);

                }

            }

            $fingerprint = BiometricDeviceService::registerFingerprint(
                $customer->id,
                $biometricDeviceId,
                (int) $deviceUserId,
                $fingerIndex,
                $this->getUserId(),
                $this->getCompanyId()
            );

            return $this->createdResponse($fingerprint, "fingerprint_registered", "biometric_fingerprint");

        }catch(\DomainException $e) {

            return response()->json(["bool" => false, "msg" => $e->getMessage()], 422);

        }catch(\Exception $e) {

            return $this->handleException($e, "register_fingerprint");

        }

    }
}
