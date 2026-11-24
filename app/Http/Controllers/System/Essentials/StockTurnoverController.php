<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\{Controller};
use App\Http\Requests\System\Essentials\{StockTurnoverRequest};
use App\Services\System\Essentials\{StockTurnoverService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{JsonResponse, Request};

final class StockTurnoverController extends Controller {

    public function index(): View {

        return view("System/general/Essentials/stock_turnover/main");

    }

    public function initParams(Request $request, StockTurnoverService $turnover): JsonResponse {

        return response()->json(["data" => $turnover->filters($request->user())]);

    }

    public function list(StockTurnoverRequest $request, StockTurnoverService $turnover): JsonResponse {

        return response()->json(["data" => $turnover->report($request->user(), $request->validated())]);

    }

}
