<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Catalogs\Subscriptions\{StoreSubscriptionRequest, UpdateSubscriptionRequest};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Catalogs\Subscriptions\{SubscriptionConfigService, SubscriptionService};
use Illuminate\Http\{JsonResponse, Request};

class SubscriptionController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Catalogs.subscription";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return SubscriptionConfigService::getInitParams($this->getCompanyId(), $page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return SubscriptionService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Catalogs/subscriptions/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse {

        try {

            $data = $this->prepareSubscriptionData($request);
            $item = SubscriptionService::create($data, $this->getCompanyId(), $this->getUserId());

            if(!Utilities::isDefined($item)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ITEMS,
                $this->getCompanyId()
            );

            return $this->createdResponse($item, "created", "item");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Subscription ID
     */
    public function update(UpdateSubscriptionRequest $request, int $id): JsonResponse {

        try {

            $item = SubscriptionService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($item)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareSubscriptionData($request);

            $item = SubscriptionService::update($item, $data, $this->getUserId());

            if(!Utilities::isDefined($item)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ITEMS,
                $this->getCompanyId()
            );

            return $this->updatedResponse($item, "updated", "item");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreSubscriptionRequest|UpdateSubscriptionRequest  $request
     */
    private function prepareSubscriptionData($request): array {

        return [
            "internal_code" => $request->input("internal_code"),
            "name" => $request->input("name"),
            "description" => $request->input("description"),
            "price" => $request->input("price"),
            "price_includes_tax" => $request->boolean("price_includes_tax"),
            "igv_exempt" => $request->boolean("igv_exempt"),
            "min_price" => $request->input("min_price"),
            "max_price" => $request->input("max_price"),
            "currency_id" => $request->input("currency_id"),
            "duration_type" => $request->input("duration_type"),
            "duration_value" => $request->input("duration_value"),
            "commission_type" => $request->input("commission_type"),
            "commission_value" => $request->input("commission_value"),
            "capacity_control_enabled" => $request->boolean("capacity_control_enabled"),
            "capacity_limit" => $request->input("capacity_limit"),
            "expires_at" => $request->input("expires_at"),
            "attendance_limit_per_day" => $request->input("attendance_limit_per_day"),
            "benefits" => $request->input("benefits"),
            "restrictions" => $request->input("restrictions"),
            "see_my_web" => $request->input("see_my_web"),
            "see_my_web_price" => $request->input("see_my_web_price"),
            "status" => $request->input("status"),
            "categories" => $request->input("categories"),
        ];

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
