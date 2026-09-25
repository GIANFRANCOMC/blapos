<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Finance;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Finance\{StoreCashRegisterRequest};
use App\Services\System\Finance\{CashRegisterConfigService, CashRegisterService};
use Illuminate\Http\{JsonResponse, Request};

final class CashRegisterController extends BaseController {
    public function __construct(private readonly CashRegisterService $service) {

    }

    public function getTranslationNamespace(): string {

        return "cash_registers";

    }

    public function index() {

        return view("System/general/Finance/cash_registers/main");

    }

    public function initParams(Request $request): JsonResponse {

        return response()->json(
            CashRegisterConfigService::getInitParams(
                (string) $request->get("page", "main"),
                $this->getUserId()
            )
        );

    }

    public function list(): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => $this->service->listRegisters($this->getCompanyId(), $this->getUserId()),
        ]);

    }

    public function store(StoreCashRegisterRequest $request): JsonResponse {

        try {

            $register = $this->service->createRegister(
                $this->getCompanyId(),
                $this->getUserId(),
                $request->validated()
            );

            return response()->json([
                "bool" => true,
                "msg" => "Caja registrada correctamente.",
                "data" => $register,
            ]);

        }catch(\Throwable $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }

    }
}
