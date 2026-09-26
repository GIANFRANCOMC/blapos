<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Finance;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Finance\{StoreCashMovementRequest};
use App\Services\System\Finance\{CashRegisterService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{JsonResponse, Request, Response};
use RuntimeException;

final class CashMovementController extends BaseController {
    public function __construct(private readonly CashRegisterService $service) {

    }

    public function index(): View {

        return view("System/general/Finance/cash_movements/main");

    }

    public function list(Request $request): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => $this->service->listMovements(
                $this->cashFilters($request),
                $this->getPerPage($request),
                $this->getUserId()
            ),
        ]);

    }

    public function store(StoreCashMovementRequest $request): JsonResponse {

        try {

            $movement = $this->service->registerMovement(
                $this->getUserId(),
                $request->validated()
            );

            return response()->json([
                "bool" => true,
                "msg" => "Movimiento registrado correctamente.",
                "data" => $movement,
            ]);

        }catch(RuntimeException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function export(Request $request): Response {

        $rows = $this->service->movementsForExport(
            $this->cashFilters($request),
            $this->getUserId()
        );

        $handle = fopen("php://temp", "r+");

        fputcsv($handle, [
            "Fecha",
            "Caja",
            "Sucursal",
            "Tipo",
            "Método de pago",
            "Referencia",
            "Responsable",
            "Importe",
        ], ";");

        foreach($rows as $row) {

            fputcsv($handle, [
                $row->occurred_at,
                $row->cashSession?->register?->name,
                $row->branch?->name,
                $row->movement_type,
                $row->paymentMethod?->name ?? "Efectivo / caja",
                $row->reference,
                $row->user?->name,
                number_format((float) $row->amount, 2, ".", ""),
            ], ";");

        }

        rewind($handle);

        $csv = stream_get_contents($handle);
        fclose($handle);

        return response("\xEF\xBB\xBF".$csv, 200, [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=blapos-caja-movimientos-".now()->format("Ymd-His").".csv",
        ]);

    }

    protected function getTranslationNamespace(): string {

        return "System.Finance.cash_movements";

    }

    private function cashFilters(Request $request): array {

        return array_filter($request->input("filter", []), fn($value) => $value !== null && $value !== "");

    }
}
