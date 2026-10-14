<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Catalogs;

use App\Exports\System\Catalogs\References\{CatalogReferenceListExport, CatalogReferenceTemplateExport};
use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Catalogs\{ImportCatalogReferencesRequest};
use App\Http\Requests\System\Catalogs\Brands\{StoreBrandRequest, UpdateBrandRequest};
use App\Imports\System\Catalogs\References\{CatalogReferenceImport};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Catalogs\Brands\{BrandConfigService, BrandService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Facades\{Excel};
use Symfony\Component\HttpFoundation\{BinaryFileResponse};

final class BrandController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Catalogs.brand";

    public function initParams(Request $request) {

        return BrandConfigService::getInitParams(
            $this->getPage($request),
            $this->getUserId()
        );

    }

    public function list(Request $request) {

        return BrandService::getPaginatedList(
            $this->getFilters($request),
            $this->getPerPage($request, Utilities::$per_page_default)
        );

    }

    public function export(Request $request): BinaryFileResponse {

        return Excel::download(
            new CatalogReferenceListExport("brands", $this->getFilters($request)),
            "marcas_".now()->format("Y-m-d_His").".xlsx"
        );

    }

    public function importTemplate(): BinaryFileResponse {

        return Excel::download(new CatalogReferenceTemplateExport("brands"), "plantilla_marcas.xlsx");

    }

    public function import(ImportCatalogReferencesRequest $request): JsonResponse {

        try {

            $import = new CatalogReferenceImport("brands", $this->getUserId());

            Excel::import($import, $request->file("file"));
            InitParamsCacheInvalidationService::invalidate(InitParamsCacheInvalidationService::BRANDS);

            return response()->json([
                "bool" => true,
                "msg" => "{$import->importedCount()} marcas importadas correctamente.",
                "data" => ["imported" => $import->importedCount()],
            ]);

        }catch(ValidationException $exception) {

            throw $exception;

        }catch(\Exception $exception) {

            return $this->handleException($exception, "create");

        }

    }

    public function index() {

        return view("System/general/Catalogs/brands/main");

    }

    public function store(StoreBrandRequest $request): JsonResponse {

        try {

            $brand = BrandService::create(
                $request->validated(),
                $this->getUserId()
            );

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BRANDS
            );

            return $this->createdResponse($brand, "created", "brand");

        }catch(\Exception $exception) {

            return $this->handleException($exception, "create");

        }

    }

    public function update(UpdateBrandRequest $request, int $id): JsonResponse {

        try {

            $brand = BrandService::findByIdInTenant($id, null);

            if(!$brand) {

                return $this->notFoundResponse();

            }

            $brand = BrandService::update(
                $brand,
                $request->validated(),
                $this->getUserId()
            );

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BRANDS
            );

            return $this->updatedResponse($brand, "updated", "brand");

        }catch(\Exception $exception) {

            return $this->handleException($exception, "update");

        }

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
