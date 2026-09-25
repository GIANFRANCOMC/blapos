<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Customers;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Customers\Tracking\{TrackingNotificationConfigService, TrackingNotificationService};
use Illuminate\Http\{JsonResponse, Request};

class TrackingNotificationController extends BaseController {
    /**
     * Translation namespace for tracking notification module
     */
    private const TRANSLATION_NAMESPACE = "System.Customers.tracking_notification";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return TrackingNotificationConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list of tracking notifications with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = ["status" => $request->input("status")];
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return TrackingNotificationService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Display the tracking notifications index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Customers/tracking_notifications/main");

    }

    public function retry(int $id): JsonResponse {

        try {

            $notification = TrackingNotificationService::retry(
                $this->getCompanyId(),
                $this->getUserId(),
                $id
            );

            return response()->json([
                "bool" => true,
                "msg" => "Notificación preparada para reintento.",
                "data" => $notification,
            ]);

        }catch(\Throwable $exception) {

            return $this->handleException($exception, "update");

        }

    }

    /**
     * Get translation namespace for tracking notification module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
