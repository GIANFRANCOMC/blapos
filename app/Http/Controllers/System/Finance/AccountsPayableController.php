<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Finance;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Finance\{AccountsPayableService};
use Illuminate\Http\{JsonResponse, Request};

final class AccountsPayableController extends BaseController {
    public function __construct(private readonly AccountsPayableService $service) {

    }

    public function index() {

        return view("System/general/Finance/accounts_payable/main");

    }

    public function getTranslationNamespace(): string {

        return "accounts_payable";

    }

    public function list(Request $request): JsonResponse {

        $filters = $this->filters($request);

        return response()->json([
            "bool" => true,
            "data" => $this->service->paginate($this->getUserId(), $filters, $this->getPerPage($request, Utilities::$per_page_default)),
            "summary" => $this->service->summary($this->getUserId(), $filters),
        ]);

    }

    public function show(int $id): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => $this->service->find($this->getUserId(), $id),
        ]);

    }

    private function filters(Request $request): array {

        return array_filter([
            "search" => $request->input("search"),
            "status" => $request->input("status"),
            "date_from" => $request->input("date_from"),
            "date_to" => $request->input("date_to"),
        ], fn($value) => $value !== null && $value !== "");

    }
}
