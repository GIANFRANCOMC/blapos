<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Warehouses;

use App\Exports\System\Warehouses\{InventoryReportExport};
use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Warehouses\{StoreInventoryGuideRequest, StoreInventoryMovementRequest, StoreInventoryOperationRequest, StoreInventoryTransferRequest};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Warehouses\Inventory\{InventoryGuideService};
use App\Services\System\Warehouses\StockManagement\{StockManagementConfigService, StockManagementService};
use Illuminate\Http\{JsonResponse, Request};
use Maatwebsite\Excel\Facades\{Excel};
use Symfony\Component\HttpFoundation\{BinaryFileResponse};

class StockManagementController extends BaseController {
    /**
     * Translation namespace for stock management module
     */
    private const TRANSLATION_NAMESPACE = "System.Warehouses.stock_management";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return StockManagementConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list of items with stock information
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $warehouseId = intval($request->input("warehouse_id"));
        $perPage = $this->getPerPage($request, Utilities::$per_page_max);

        if(
            !StockManagementService::validateWarehouse($warehouseId)
            || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)
        ) {

            return response()->json([
                "data" => [],
                "total" => 0,
            ]);

        }

        return StockManagementService::getPaginatedList(
            $warehouseId,
            $perPage,
            (string) $request->input("product_search", "")
        );

    }

    public function summary(Request $request): JsonResponse {

        $allowedWarehouseIds = AccessScopeService::allowedIds(
            $this->getAuthUser(),
            AccessScopeService::WAREHOUSE
        );

        return response()->json([
            "bool" => true,
            "data" => StockManagementService::getConsolidatedStock(
                (string) $request->input("product_search", ""),
                $allowedWarehouseIds
            ),
        ]);

    }

    /**
     * Display the stock management index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Warehouses/stocks_management/main");

    }

    public function movements(Request $request) {

        $perPage = $this->getPerPage($request, Utilities::$per_page_max);
        $filters = $request->only([
            "warehouse_id",
            "item_id",
            "movement_type",
            "origin_types",
            "product_search",
            "date_from",
            "date_to",
        ]);

        $warehouseId = (int) ($filters["warehouse_id"] ?? 0);

        if($warehouseId > 0 && !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            return response()->json(["data" => [], "total" => 0]);

        }

        return StockManagementService::getKardex(
            $filters,
            $perPage
        );

    }

    public function alerts(Request $request) {

        $warehouseId = (int) $request->input("warehouse_id");

        if($warehouseId > 0 && !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            return response()->json(["data" => [], "total" => 0]);

        }

        return StockManagementService::getStockAlerts(
            $request->only(["warehouse_id", "status"]),
            $this->getPerPage($request, Utilities::$per_page_max)
        );

    }

    public function guides(Request $request) {

        $allowedWarehouseIds = AccessScopeService::allowedIds(
            $this->getAuthUser(),
            AccessScopeService::WAREHOUSE
        );

        return InventoryGuideService::query(
            $request->only(["warehouse_id", "guide_type", "date_from", "date_to"])
        )
            ->when(
                $allowedWarehouseIds !== null,
                fn($query) => $query->whereIn("warehouse_id", $allowedWarehouseIds)
            )
            ->paginate($this->getPerPage($request, Utilities::$per_page_max));

    }

    public function storeGuide(StoreInventoryGuideRequest $request): JsonResponse {

        try {

            $warehouseId = (int) $request->warehouse_id;

            if(!AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

                return $this->errorResponse("warehouse_not_available", [], 403);

            }

            $guide = InventoryGuideService::create(
                $this->getUserId(),
                $request->validated()
            );

            return $this->createdResponse($guide, "created", "inventoryGuide");

        }catch(\Throwable $e) {

            return $this->handleException($e, "create");

        }

    }

    public function storeOperations(StoreInventoryOperationRequest $request): JsonResponse {

        $data = $request->validated();

        try {

            $warehouse = StockManagementService::validateWarehouse(
                (int) $data["warehouse_id"]
            );

            if(!$warehouse || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, (int) $warehouse->id)) {

                return $this->errorResponse("warehouse_not_available");

            }

            $movements = StockManagementService::createManualMovements(
                (int) $warehouse->id,
                (string) $data["movement_type"],
                (string) $data["origin_type"],
                $data["items"],
                (string) $data["reason"],
                $this->getUserId()
            );

            return response()->json([
                "bool" => true,
                "msg" => count($movements) === 1
                    ? "Operación registrada correctamente."
                    : "Operación registrada para todos los productos.",
                "data" => $movements,
            ]);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    public function export(Request $request): BinaryFileResponse {

        $view = in_array($request->input("view"), ["stock", "kardex", "transfers", "valued", "guides"], true)
            ? (string) $request->input("view")
            : "stock";

        $filters = $request->only([
            "warehouse_id",
            "item_id",
            "movement_type",
            "origin_types",
            "product_search",
            "guide_type",
            "date_from",
            "date_to",
        ]);

        if(($filters["warehouse_id"] ?? null) !== "all") {

            $warehouseId = (int) ($filters["warehouse_id"] ?? 0);
            $warehouse = StockManagementService::validateWarehouse($warehouseId);

            if(!$warehouse || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

                abort(404, "El almacén seleccionado no está disponible.");

            }

        }else {

            $filters["allowed_warehouse_ids"] = AccessScopeService::allowedIds(
                $this->getAuthUser(),
                AccessScopeService::WAREHOUSE
            );

        }
        $fileName = "inventario_{$view}_".now()->format("Y-m-d_His").".xlsx";

        return Excel::download(
            new InventoryReportExport($view, $filters),
            $fileName
        );

    }

    public function storeMovement(StoreInventoryMovementRequest $request): JsonResponse {

        $data = $request->validated();

        try {

            $warehouse = StockManagementService::validateWarehouse(
                (int) $data["warehouse_id"]
            );

            if(!$warehouse || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, (int) $warehouse->id)) {

                return $this->errorResponse("warehouse_not_available");

            }

            $movement = StockManagementService::createManualMovement(
                (int) $warehouse->id,
                (int) $data["item_id"],
                (string) $data["movement_type"],
                isset($data["quantity"]) ? (float) $data["quantity"] : null,
                isset($data["resulting_balance"]) ? (float) $data["resulting_balance"] : null,
                (string) $data["reason"],
                (string) $data["origin_type"],
                $this->getUserId()
            );

            return response()->json([
                "bool" => true,
                "msg" => "Movimiento registrado correctamente.",
                "data" => $movement,
            ]);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    public function storeTransfer(StoreInventoryTransferRequest $request): JsonResponse {

        $data = $request->validated();

        try {

            if(
                !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, (int) $data["source_warehouse_id"])
                || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, (int) $data["destination_warehouse_id"])
            ) {

                return $this->errorResponse("warehouse_not_available", [], 403);

            }

            $transfer = StockManagementService::transfer([
                "source_warehouse_id" => (int) $data["source_warehouse_id"],
                "destination_warehouse_id" => (int) $data["destination_warehouse_id"],
                "items" => $data["items"],
                "reason" => (string) $data["reason"],
                "user_id" => $this->getUserId(),
            ]);

            return response()->json([
                "bool" => true,
                "msg" => count($data["items"]) === 1
                    ? "Traslado registrado correctamente."
                    : "Productos trasladados correctamente.",
                "data" => $transfer,
            ]);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    /**
     * Get translation namespace for stock management module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
