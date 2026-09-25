<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Customers;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Customers\TrackingSubscriptions\{CancelTrackingSubscriptionRequest, RenewTrackingSubscriptionRequest, StoreManualTrackingSubscriptionRequest};
use App\Models\System\Customers\{Subscription};
use App\Services\System\Customers\Tracking\{TrackingSubscriptionConfigService, TrackingSubscriptionService};
use App\Services\System\Organizations\{AccessScopeService};
use Illuminate\Http\{JsonResponse, Request};

class TrackingSubscriptionController extends BaseController {
    /**
     * Translation namespace for tracking subscription module
     */
    private const TRANSLATION_NAMESPACE = "System.Customers.tracking_subscription";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return TrackingSubscriptionConfigService::getInitParams($this->getCompanyId(), $page, $this->getUserId());

    }

    /**
     * Get paginated list of subscriptions with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = [
            "branch_id" => $request->input("branch_id"),
            "customer_id" => $request->input("customer_id"),
            "start_date" => $request->input("start_date"),
            "end_date" => $request->input("end_date"),
            "status" => $request->input("status"),
        ];

        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return TrackingSubscriptionService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Display the tracking subscriptions index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Customers/tracking_subscriptions/main");

    }

    public function storeManual(StoreManualTrackingSubscriptionRequest $request): JsonResponse {

        try {

            $validated = $request->validated();

            if(!AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::BRANCH, (int) $validated["branch_id"])) {

                return $this->notFoundResponse();

            }

            $subscription = TrackingSubscriptionService::createManual(
                $this->getCompanyId(),
                $validated,
                $this->getUserId()
            );

            return $this->createdResponse($subscription, "created", "subscription");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Cancel the specified subscription
     *
     * @param  int  $id Subscription ID
     */
    public function cancel(CancelTrackingSubscriptionRequest $request, int $id): JsonResponse {

        try {

            $subscription = Subscription::query()
                ->find($id);

            if(!$subscription
                || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::BRANCH, (int) $subscription->branch_id)) {

                return $this->notFoundResponse();

            }

            $subscription = TrackingSubscriptionService::cancel($subscription, $request->motive, $this->getUserId());

            return $this->updatedResponse($subscription, "canceled", "subscription");

        }catch(\Exception $e) {

            return $this->handleException($e, "cancel");

        }

    }

    public function renew(RenewTrackingSubscriptionRequest $request, int $id): JsonResponse {

        try {

            $subscription = Subscription::query()
                ->find($id);

            if(!$subscription
                || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::BRANCH, (int) $subscription->branch_id)) {

                return $this->notFoundResponse();

            }

            $renewal = TrackingSubscriptionService::renew(
                $subscription,
                $request->validated(),
                $this->getUserId()
            );

            return $this->createdResponse($renewal, "created", "subscription");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Get translation namespace for tracking subscription module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
