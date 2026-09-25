<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Sales;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Sales\{QuotationService, SaleConfigService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Validator};

final class QuotationController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Sales.quotations";

    public function index() {

        return view("System/general/Sales/quotations/main", ["pageMode" => "list"]);

    }

    public function create() {

        return view("System/general/Sales/quotations/main", ["pageMode" => "create"]);

    }

    public function initParams(Request $request) {

        return SaleConfigService::getInitParams("main", $this->getUserId());

    }

    public function list(Request $request) {

        return QuotationService::query(
            $this->getCompanyId(),
            [
                "word" => $request->input("word"),
                "status" => $request->input("status"),
            ]
        )->paginate($this->getPerPage($request, Utilities::$per_page_default));

    }

    public function show(int $id): JsonResponse {

        return response()->json(QuotationService::find($this->getCompanyId(), $id));

    }

    public function saleDraft(int $id): JsonResponse {

        try {

            return response()->json([
                "bool" => true,
                "data" => QuotationService::saleDraft($this->getCompanyId(), $id),
            ]);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function store(Request $request): JsonResponse {

        $round = Utilities::$inputs["round"];
        $validator = Validator::make($request->all(), [
            "branch_id" => ["nullable", "integer"],
            "holder_id" => ["required", "integer"],
            "currency_id" => ["required", "integer"],
            "issue_date" => ["required", "date"],
            "valid_until" => ["nullable", "date", "after_or_equal:issue_date"],
            "observation" => ["nullable", "string", "max:2000"],
            "taxes" => ["nullable", "array", "max:20"],
            "taxes.*.tax_id" => ["required_with:taxes", "integer"],
            "taxes.*.quantity" => ["nullable", "integer", "min:1"],
            "details" => ["required", "array", "min:1", "max:100"],
            "details.*.item_id" => ["required", "integer"],
            "details.*.currency_id" => ["nullable", "integer"],
            "details.*.name" => ["nullable", "string", "max:255"],
            "details.*.quantity" => ["required", "numeric", "gt:0", "decimal:0,$round"],
            "details.*.price" => ["nullable", "numeric", "min:0", "decimal:0,$round"],
            "details.*.price_includes_tax" => ["nullable", "boolean"],
            "details.*.igv_exempt" => ["nullable", "boolean"],
            "details.*.observation" => ["nullable", "string", "max:1000"],
        ], [
            "required" => "Campo obligatorio.",
            "details.min" => "Agrega al menos un detalle.",
            "numeric" => "Ingresa un número válido.",
            "gt" => "Debe ser mayor que cero.",
            "decimal" => "Usa hasta {$round} decimales.",
            "after_or_equal" => "No puede ser anterior a la fecha de emisión.",
        ]);

        if($validator->fails()) {

            return response()->json(["bool" => false, "errors" => $validator->errors()], 422);

        }

        try {

            return response()->json([
                "bool" => true,
                "msg" => "Cotización registrada correctamente.",
                "data" => QuotationService::create($this->getCompanyId(), $this->getUserId(), $validator->validated()),
            ], 201);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function cancel(int $id): JsonResponse {

        try {

            return response()->json([
                "bool" => true,
                "msg" => "Cotización anulada correctamente.",
                "data" => QuotationService::cancel($this->getCompanyId(), $id, $this->getUserId()),
            ]);

        }catch(\Throwable $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
