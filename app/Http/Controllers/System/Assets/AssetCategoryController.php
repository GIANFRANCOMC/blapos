<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Assets;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Assets\AssetCategories\{StoreAssetCategoryRequest, UpdateAssetCategoryRequest};
use App\Models\System\Assets\{AssetCategory};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use Illuminate\Http\{JsonResponse};

final class AssetCategoryController extends BaseController {
    public function list() {

        return AssetCategory::query()
            ->withCount("assets")
            ->orderBy("name")
            ->get();

    }

    public function store(StoreAssetCategoryRequest $request): JsonResponse {

        $data = $request->validated();
        $category = AssetCategory::create([
            ...$data,
            "status" => $data["status"] ?? "active",
            "created_by" => $this->getUserId(),
        ]);
        InitParamsCacheInvalidationService::invalidate(InitParamsCacheInvalidationService::ASSETS);

        return response()->json(["bool" => true, "msg" => "Categoría de activo agregada.", "data" => $category], 201);

    }

    public function update(UpdateAssetCategoryRequest $request, int $id): JsonResponse {

        $category = AssetCategory::query()->findOrFail($id);
        $data = $request->validated();
        $category->fill([...$data, "updated_by" => $this->getUserId()])->save();
        InitParamsCacheInvalidationService::invalidate(InitParamsCacheInvalidationService::ASSETS);

        return response()->json(["bool" => true, "msg" => "Categoría de activo actualizada.", "data" => $category]);

    }

    protected function getTranslationNamespace(): string {

        return "System.Assets.asset";

    }
}
