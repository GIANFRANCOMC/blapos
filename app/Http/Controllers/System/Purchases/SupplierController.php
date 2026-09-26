<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Purchases;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Purchases\{StoreSupplierRequest};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Purchases\{SupplierService};
use Illuminate\Http\{JsonResponse, Request};

final class SupplierController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Purchases.supplier";

    public function index() {

        return view("System/general/Purchases/suppliers/main");

    }

    public function list(Request $request) {

        return SupplierService::query(
            (string) $request->input("word", "")
        )->paginate($this->getPerPage($request, Utilities::$per_page_default));

    }

    public function store(StoreSupplierRequest $request): JsonResponse {

        $supplier = SupplierService::create(
            $this->getUserId(),
            $request->validated()
        );

        InitParamsCacheInvalidationService::invalidate(
            InitParamsCacheInvalidationService::SUPPLIERS
        );

        return response()->json([
            "bool" => true,
            "msg" => "Proveedor agregado correctamente.",
            "data" => $supplier,
        ], 201);

    }

    public function update(StoreSupplierRequest $request, int $id): JsonResponse {

        $supplier = SupplierService::update(
            $id,
            $this->getUserId(),
            $request->validated()
        );

        InitParamsCacheInvalidationService::invalidate(
            InitParamsCacheInvalidationService::SUPPLIERS
        );

        return response()->json([
            "bool" => true,
            "msg" => "Proveedor actualizado correctamente.",
            "data" => $supplier,
        ]);

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
