<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Catalogs;

use App\Exports\System\Catalogs\Products\{ProductImportTemplateExport, ProductListExport};
use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Catalogs\Products\{ImportProductsRequest, StoreProductRequest, UpdateProductRequest};
use App\Imports\System\Catalogs\Products\{ProductBasicImport};
use App\Models\System\Organizations\{Company};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Catalogs\Products\{ProductConfigService, ProductService};
use App\Services\System\Warehouses\StockManagement\{StockManagementService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Facades\{Excel};
use Symfony\Component\HttpFoundation\{BinaryFileResponse};

class ProductController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Catalogs.product";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return ProductConfigService::getInitParams($this->getCompanyId(), $page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return ProductService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Download every product matching the current list filters.
     */
    public function export(Request $request): BinaryFileResponse {

        $fileName = "productos_".now()->format("Y-m-d_His").".xlsx";

        return Excel::download(
            new ProductListExport($this->getCompanyId(), $this->getFilters($request)),
            $fileName
        );

    }

    public function importTemplate(): BinaryFileResponse {

        return Excel::download(
            new ProductImportTemplateExport(),
            "plantilla_productos.xlsx"
        );

    }

    public function import(ImportProductsRequest $request): JsonResponse {

        try {

            $warehouse = StockManagementService::validateWarehouse(
                (int) $request->validated("warehouse_id"),
                $this->getCompanyId()
            );

            if(!$warehouse) {

                return response()->json([
                    "bool" => false,
                    "msg" => "El almacén seleccionado no está disponible.",
                ], 422);

            }

            $currencyId = (int) Company::whereKey($this->getCompanyId())->value("currency_id");

            $import = new ProductBasicImport(
                $this->getCompanyId(),
                $currencyId,
                (int) $warehouse->id,
                $this->getUserId()
            );

            Excel::import($import, $request->file("file"));

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ITEMS,
                $this->getCompanyId()
            );

            return response()->json([
                "bool" => true,
                "msg" => "{$import->importedCount()} productos importados correctamente.",
                "data" => ["imported" => $import->importedCount()],
            ]);

        }catch(ValidationException $e) {

            throw $e;

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Catalogs/products/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreProductRequest $request): JsonResponse {

        try {

            $data = $this->prepareProductData($request);
            $item = ProductService::create($data, $this->getCompanyId(), $this->getUserId());

            if(!Utilities::isDefined($item)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ITEMS,
                $this->getCompanyId()
            );

            return $this->createdResponse($item, "created", "item");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Product ID
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse {

        try {

            $item = ProductService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($item)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareProductData($request);

            $item = ProductService::update($item, $data, $this->getUserId());

            if(!Utilities::isDefined($item)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ITEMS,
                $this->getCompanyId()
            );

            return $this->updatedResponse($item, "updated", "item");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreProductRequest|UpdateProductRequest  $request
     */
    private function prepareProductData($request): array {

        return [
            "brand_id" => $request->input("brand_id"),
            "internal_code" => $request->input("internal_code"),
            "barcode" => $request->input("barcode"),
            "name" => $request->input("name"),
            "description" => $request->input("description"),
            "price" => $request->input("price"),
            "price_includes_tax" => $request->boolean("price_includes_tax"),
            "igv_exempt" => $request->boolean("igv_exempt"),
            "min_price" => $request->input("min_price"),
            "max_price" => $request->input("max_price"),
            "commission_type" => $request->input("commission_type"),
            "commission_value" => $request->input("commission_value"),
            "currency_id" => $request->input("currency_id"),
            "expires_at" => $request->input("expires_at"),
            "see_my_web" => $request->input("see_my_web"),
            "see_my_web_price" => $request->input("see_my_web_price"),
            "status" => $request->input("status"),
            "categories" => $request->input("categories"),
            "inventory" => $request->input("inventory", []),
        ];

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
