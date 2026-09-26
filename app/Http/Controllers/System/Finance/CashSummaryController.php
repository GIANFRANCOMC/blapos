<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Finance;

use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Finance\{CashRegisterService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{JsonResponse, Request};

final class CashSummaryController extends BaseController {
    public function __construct(private readonly CashRegisterService $service) {

    }

    public function index(): View {

        return view("System/general/Finance/cash_summary/main");

    }

    public function data(Request $request): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => $this->service->summary(
                $this->cashFilters($request),
                $this->getUserId()
            ),
        ]);

    }

    protected function getTranslationNamespace(): string {

        return "System.Finance.cash_summary";

    }

    private function cashFilters(Request $request): array {

        return array_filter($request->input("filter", []), fn($value) => $value !== null && $value !== "");

    }
}
