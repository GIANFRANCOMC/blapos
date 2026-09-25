<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Catalogs;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Catalogs\Recipes\{RecipeWarehouseRequest, StoreRecipeRequest, StoreRecipeWasteRequest, UpdateRecipeRequest};
use App\Models\System\Catalogs\{RecipeDish};
use App\Services\System\Base\{CompanyReferenceDataService, InitParamsCacheInvalidationService};
use App\Services\System\Catalogs\Recipes\{RecipeConfigService, RecipeService, RecipeWasteService};
use Illuminate\Http\{JsonResponse, Request};

class RecipeController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Catalogs.recipe";

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

    public function index() {

        return view("System/general/Catalogs/recipes/main");

    }

    public function initParams(Request $request) {

        return RecipeConfigService::getInitParams(
            $this->getPage($request),
            $this->getUserId()
        );

    }

    public function list(Request $request) {

        return RecipeService::getPaginatedList(
            $this->getCompanyId(),
            $this->getFilters($request),
            $this->getPerPage($request, Utilities::$per_page_default)
        );

    }

    public function store(StoreRecipeRequest $request): JsonResponse {

        try {

            $recipe = RecipeService::create($request->validated(), $this->getCompanyId(), $this->getUserId());
            InitParamsCacheInvalidationService::invalidate(InitParamsCacheInvalidationService::ITEMS, $this->getCompanyId());

            return response()->json([
                "bool" => true,
                "msg" => "Receta o platillo agregado correctamente.",
                "data" => $recipe,
            ], 201);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    public function update(UpdateRecipeRequest $request, int $id): JsonResponse {

        try {

            $recipe = RecipeDish::query()
                ->with(["item"])
                ->findOrFail($id);

            $recipe = RecipeService::update($recipe, $request->validated(), $this->getCompanyId(), $this->getUserId());
            InitParamsCacheInvalidationService::invalidate(InitParamsCacheInvalidationService::ITEMS, $this->getCompanyId());

            return response()->json([
                "bool" => true,
                "msg" => "Receta o platillo actualizado correctamente.",
                "data" => $recipe,
            ]);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    public function show(int $id): JsonResponse {

        $recipe = RecipeDish::with([
            "item.brand",
            "item.currency",
            "components.item",
            "dishToppings.topping.components.item",
            "options.components.item",
        ])
            ->findOrFail($id);

        return response()->json($recipe);

    }

    public function theoreticalCost(RecipeWarehouseRequest $request, int $id): JsonResponse {

        $data = $request->validated();

        try {

            return response()->json([
                "bool" => true,
                "data" => RecipeService::theoreticalCost(
                    $id,
                    (int) $data["warehouse_id"],
                    $this->getCompanyId(),
                    CompanyReferenceDataService::forUser($this->getUserId())
                        ->allowedWarehouseIds()
                ),
            ]);

        }catch(\DomainException $exception) {

            return response()->json([
                "bool" => false,
                "msg" => $exception->getMessage(),
            ], 422);

        }

    }

    public function wasteRecords(Request $request): JsonResponse {

        return response()->json([
            "bool" => true,
            "data" => RecipeWasteService::list(
                $this->getCompanyId(),
                $request->only(["recipe_dish_id", "warehouse_id", "item_id", "date_from", "date_to"]),
                $this->getPerPage($request),
                CompanyReferenceDataService::forUser($this->getUserId())
                    ->allowedWarehouseIds()
            ),
        ]);

    }

    public function storeWaste(StoreRecipeWasteRequest $request, int $id): JsonResponse {

        $data = $request->validated();

        try {

            return response()->json([
                "bool" => true,
                "msg" => "Merma registrada y descontada del inventario.",
                "data" => RecipeWasteService::register(
                    $id,
                    $this->getCompanyId(),
                    $this->getUserId(),
                    $data,
                    CompanyReferenceDataService::forUser($this->getUserId())
                        ->allowedWarehouseIds()
                ),
            ], 201);

        }catch(\DomainException $exception) {

            return response()->json(["bool" => false, "msg" => $exception->getMessage()], 422);

        }

    }

    public function destroy(int $id): JsonResponse {

        try {

            $recipe = RecipeDish::query()
                ->findOrFail($id);

            RecipeService::delete($recipe, $this->getCompanyId());
            InitParamsCacheInvalidationService::invalidate(InitParamsCacheInvalidationService::ITEMS, $this->getCompanyId());

            return response()->json([
                "bool" => true,
                "msg" => "Receta o platillo eliminado correctamente.",
            ]);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }
}
