<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Catalogs\Categories\{StoreCategoryRequest, UpdateCategoryRequest};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Catalogs\Categories\{CategoryConfigService, CategoryService};
use Illuminate\Http\{JsonResponse, Request};

class CategoryController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Catalogs.category";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return CategoryConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return CategoryService::getPaginatedList($filters, $perPage);

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Catalogs/categories/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreCategoryRequest $request): JsonResponse {

        try {

            $data = $this->prepareCategoryData($request);
            $category = CategoryService::create($data, $this->getUserId());

            if(!Utilities::isDefined($category)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::CATEGORIES
            );

            return $this->createdResponse($category, "created", "category");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Category ID
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse {

        try {

            $category = CategoryService::findByIdInTenant($id, null);

            if(!Utilities::isDefined($category)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareCategoryData($request);

            $category = CategoryService::update($category, $data, $this->getUserId());

            if(!Utilities::isDefined($category)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::CATEGORIES
            );

            return $this->updatedResponse($category, "updated", "category");

        }catch(\DomainException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Remove the specified record
     * Deletes the category only when no active product dependency exists.
     *
     * @param  int  $id Category ID
     */
    public function destroy(int $id): JsonResponse {

        try {

            CategoryService::delete($id);
            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::CATEGORIES
            );

            return response()->json(["bool" => true, "msg" => "Categoría eliminada correctamente."]);

        }catch(\DomainException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreCategoryRequest|UpdateCategoryRequest  $request
     */
    private function prepareCategoryData($request): array {

        return [
            "internal_code" => $request->input("internal_code"),
            "name" => $request->input("name"),
            "description" => $request->input("description"),
            "sort_order" => $request->input("sort_order", 1),
            "is_public" => $request->boolean("is_public", true),
            "status" => $request->input("status"),
        ];

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
