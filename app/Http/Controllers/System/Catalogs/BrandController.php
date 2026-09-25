<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Catalogs\Brands\{StoreBrandRequest, UpdateBrandRequest};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Catalogs\Brands\{BrandConfigService, BrandService};
use Illuminate\Http\{JsonResponse, Request};

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
            $this->getCompanyId(),
            $this->getFilters($request),
            $this->getPerPage($request, Utilities::$per_page_default)
        );

    }

    public function index() {

        return view("System/general/Catalogs/brands/main");

    }

    public function store(StoreBrandRequest $request): JsonResponse {

        try {

            $brand = BrandService::create(
                $request->validated(),
                $this->getCompanyId(),
                $this->getUserId()
            );

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BRANDS,
                $this->getCompanyId()
            );

            return $this->createdResponse($brand, "created", "brand");

        }catch(\Exception $exception) {

            return $this->handleException($exception, "create");

        }

    }

    public function update(UpdateBrandRequest $request, int $id): JsonResponse {

        try {

            $brand = BrandService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!$brand) {

                return $this->notFoundResponse();

            }

            $brand = BrandService::update(
                $brand,
                $request->validated(),
                $this->getCompanyId(),
                $this->getUserId()
            );

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BRANDS,
                $this->getCompanyId()
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
