<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Essentials\{DashboardConfigService, DashboardService};
use Illuminate\Http\{JsonResponse, Request};

class DashboardController extends BaseController {
    /**
     * Translation namespace for dashboard module
     */
    private const TRANSLATION_NAMESPACE = "System.Essentials.dashboard";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return DashboardConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Display the dashboard index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Essentials/dashboard/main");

    }

    /**
     * Get dashboard data for a specific date
     */
    public function initData(Request $request): JsonResponse {

        $date = Utilities::isDefined($request->date) && Utilities::isValidDateFormat($request->date)
                ? $request->date
                : date("Y-m-d");

        $branchId = $request->filled("branch_id") ? (int) $request->input("branch_id") : null;
        $data = DashboardService::getDashboardData($this->getCompanyId(), $date, $branchId);

        return $this->successResponse($data, "data_obtained");

    }

    /**
     * Get translation namespace for dashboard module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
