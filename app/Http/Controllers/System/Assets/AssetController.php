<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Assets;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Assets\Assets\{StoreAssetRequest, UpdateAssetRequest};
use App\Services\System\Assets\Assets\{AssetConfigService, AssetService};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use Illuminate\Http\{JsonResponse, Request};

class AssetController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Assets.asset";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return AssetConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return AssetService::getPaginatedList($filters, $perPage);

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Assets/assets/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreAssetRequest $request): JsonResponse {

        try {

            $data = $this->prepareAssetData($request);
            $asset = AssetService::create($data, $this->getUserId());

            if(!Utilities::isDefined($asset)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ASSETS
            );

            return $this->createdResponse($asset, "created", "asset");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Asset ID
     */
    public function update(UpdateAssetRequest $request, int $id): JsonResponse {

        try {

            $asset = AssetService::findByIdInTenant($id, null);

            if(!Utilities::isDefined($asset)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareAssetData($request);

            $asset = AssetService::update($asset, $data, $this->getUserId());

            if(!Utilities::isDefined($asset)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ASSETS
            );

            return $this->updatedResponse($asset, "updated", "asset");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreAssetRequest|UpdateAssetRequest  $request
     */
    private function prepareAssetData($request): array {

        return [
            "asset_category_id" => $request->input("asset_category_id"),
            "internal_code" => $request->input("internal_code"),
            "patrimonial_code" => $request->input("patrimonial_code"),
            "serial_number" => $request->input("serial_number"),
            "name" => $request->input("name"),
            "description" => $request->input("description"),
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
