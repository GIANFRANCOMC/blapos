<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Purchases;

use App\Exports\System\Purchases\{PurchaseListExport};
use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Purchases\{ReceivePurchaseRequest, StorePurchaseRequest, StorePurchaseReturnRequest};
use App\Services\System\Purchases\{PurchaseConfigService, PurchaseReturnService, PurchaseService};
use Illuminate\Http\{JsonResponse, Request};
use Maatwebsite\Excel\Facades\{Excel};
use Symfony\Component\HttpFoundation\{BinaryFileResponse};

final class PurchaseController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Purchases.purchase";

    public function index() {

        return view("System/general/Purchases/purchases/main");

    }

    public function create() {

        return view("System/general/Purchases/purchases/main");

    }

    public function pendingReceipts() {

        return view("System/general/Purchases/purchases/main");

    }

    public function initParams(Request $request) {

        return PurchaseConfigService::getInitParams(
            $this->getPage($request),
            $this->getUserId()
        );

    }

    public function list(Request $request) {

        return PurchaseService::getFilteredQuery(
            [
                "word" => $request->input("word"),
                "status" => $request->input("status"),
            ],
            $this->getUserId()
        )->paginate($this->getPerPage($request, Utilities::$per_page_default));

    }

    public function store(StorePurchaseRequest $request): JsonResponse {

        try {

            $purchase = PurchaseService::create(
                $this->getUserId(),
                $request->validated()
            );

            $validated = $request->validated();
            $message = ($validated["approval_status"] ?? "approved") !== "approved"
                ? "Orden registrada y pendiente de aprobación. El inventario aún no cambió."
                : (($validated["delivery_mode"] ?? "immediate") === "immediate"
                    ? "Compra registrada. La mercadería ingresó al inventario del almacén seleccionado."
                    : "Compra registrada. El stock se actualizará cuando registres la recepción de mercadería.");

            return response()->json([
                "bool" => true,
                "msg" => $message,
                "data" => $purchase,
            ], 201);

        }catch(\Throwable $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }

    }

    public function show(int $id): JsonResponse {

        return response()->json(PurchaseService::find($id));

    }

    public function receive(ReceivePurchaseRequest $request, int $id): JsonResponse {

        try {

            $receipt = PurchaseService::receive(
                $id,
                $this->getUserId(),
                $request->validated()
            );

            return response()->json([
                "bool" => true,
                "msg" => "Recepción registrada. Las existencias y el costo promedio fueron actualizados.",
                "data" => $receipt,
            ]);

        }catch(\Throwable $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }

    }

    public function cancel(int $id): JsonResponse {

        try {

            $purchase = PurchaseService::cancel(
                $id,
                $this->getUserId()
            );

            $message = match (true) {
                (bool) $purchase->getAttribute("inventory_reverted_on_cancellation") => "Compra anulada correctamente. Las recepciones asociadas fueron revertidas en inventario.",
                (bool) $purchase->getAttribute("had_inventory_receipts") => "Compra anulada correctamente. Revisa si corresponde registrar devolución a proveedor desde Inventario.",
                default => "Compra anulada. No se modificó el inventario porque no tenía recepciones."
            };

            return response()->json([
                "bool" => true,
                "msg" => $message,
                "data" => $purchase,
            ]);

        }catch(\Throwable $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }

    }

    public function returnToSupplier(StorePurchaseReturnRequest $request, int $id): JsonResponse {

        try {

            $return = PurchaseReturnService::create(
                $id,
                $this->getUserId(),
                $request->validated()
            );

            return response()->json([
                "bool" => true,
                "msg" => "Devolución registrada y existencias actualizadas.",
                "data" => $return,
            ], 201);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function approve(int $id): JsonResponse {

        try {

            return response()->json([
                "bool" => true,
                "msg" => "Orden aprobada correctamente.",
                "data" => PurchaseService::approve($id, $this->getUserId()),
            ]);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function export(Request $request): BinaryFileResponse {

        return Excel::download(
            new PurchaseListExport([
                "word" => $request->input("word"),
                "status" => $request->input("status"),
            ], $this->getUserId()),
            "compras_".now()->format("Y-m-d_His").".xlsx"
        );

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
