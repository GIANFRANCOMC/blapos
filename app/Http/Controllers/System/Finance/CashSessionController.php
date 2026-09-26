<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Finance;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Finance\{CloseCashSessionRequest, OpenCashSessionRequest};
use App\Services\System\Finance\{CashRegisterService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{JsonResponse, Request};
use RuntimeException;

final class CashSessionController extends BaseController {
    public function __construct(private readonly CashRegisterService $service) {

    }

    public function index(): View {

        return view("System/general/Finance/cash_sessions/main");

    }

    public function list(Request $request): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => $this->service->listSessions(
                $this->cashFilters($request),
                $this->getPerPage($request),
                $this->getUserId()
            ),
        ]);

    }

    public function open(OpenCashSessionRequest $request): JsonResponse {

        try {

            $session = $this->service->openSession($this->getUserId(), $request->validated());

            return response()->json(["bool" => true, "msg" => "Caja aperturada correctamente.", "data" => $session]);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function close(CloseCashSessionRequest $request): JsonResponse {

        try {

            $session = $this->service->closeSession($this->getUserId(), $request->validated());

            return response()->json([
                "bool" => true,
                "msg" => "Caja cerrada correctamente. Revisa el arqueo para confirmar diferencias.",
                "data" => $session,
            ]);

        }catch(RuntimeException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    protected function getTranslationNamespace(): string {

        return "System.Finance.cash_sessions";

    }

    private function cashFilters(Request $request): array {

        return array_filter($request->input("filter", []), fn($value) => $value !== null && $value !== "");

    }
}
