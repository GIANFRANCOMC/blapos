<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\{Controller};
use App\Http\Requests\System\Essentials\{CommercialPerformanceRequest};
use App\Services\System\Essentials\{CommercialPerformanceService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{JsonResponse, Request};

final class CommercialPerformanceController extends Controller {

    public function index(): View {

        return view("System/general/Essentials/commercial_performance/main");

    }

    public function initParams(Request $request, CommercialPerformanceService $performance): JsonResponse {

        return response()->json(["data" => $performance->filters($request->user())]);

    }

    public function list(CommercialPerformanceRequest $request, CommercialPerformanceService $performance): JsonResponse {

        return response()->json(["data" => $performance->report(
            $request->user(),
            $request->validated(),
            $request->boolean("include_evolution", true)
        )]);

    }

}
