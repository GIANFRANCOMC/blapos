<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Warehouses;

use App\Exports\System\Warehouses\{InventoryOperationTemplateExport, InventoryReportExport, StockAlertsExport};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Warehouses\{PreviewInventoryOperationRequest, StoreInventoryGuideRequest, StoreInventoryMovementRequest, StoreInventoryOperationRequest, StoreInventoryTransferRequest};
use App\Imports\System\Warehouses\{InventoryOperationPreviewImport};
use App\Models\System\Warehouses\{Warehouse};
use App\Models\System\Organizations\{Company};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Warehouses\Inventory\{InventoryGuideService};
use App\Services\System\Warehouses\StockManagement\{StockAlertsPdfRenderer, StockManagementConfigService, StockManagementService};
use App\Support\System\Warehouses\{InventoryOperationTemplate};
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Facades\{Excel};
use Symfony\Component\HttpFoundation\{BinaryFileResponse};

class StockManagementController extends BaseController {

    private const MAX_LIST_PER_PAGE = 30;
    private const DEFAULT_STOCK_PER_PAGE = 10;

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

        if($page === "main") {

            $page = "stock";

        }

        if(!in_array($page, ["stock", "transfers", "guides", "kardex", "valued"], true)) {

            abort(422, "La página de inventario no está disponible.");

        }

        if(!RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.{$page}.index")) {

            abort(403, "No tienes permiso para consultar esta página.");

        }

        return StockManagementConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list of items with stock information
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $warehouseId = intval($request->input("warehouse_id"));
        $perPage = $this->inventoryPerPage($request, self::DEFAULT_STOCK_PER_PAGE);

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
            (string) $request->input("product_search", ""),
            (string) $request->input("situation", ""),
            $this->stockFilters($request)
        );

    }

    public function summary(Request $request): JsonResponse {

        $allowedWarehouseIds = AccessScopeService::allowedIds(
            $this->getAuthUser(),
            AccessScopeService::WAREHOUSE
        );

        return response()->json([
            "bool" => true,
            "data" => StockManagementService::getPaginatedConsolidatedStock(
                (string) $request->input("product_search", ""),
                $allowedWarehouseIds,
                $this->inventoryPerPage($request, self::DEFAULT_STOCK_PER_PAGE),
                (string) $request->input("situation", ""),
                $this->stockFilters($request)
            ),
        ]);

    }

    /** Redirect the old module entry point to the stock page. */
    public function index() {

        return redirect()->route("stocks_management.stock.index");

    }

    public function movements(Request $request) {

        $perPage = $this->inventoryPerPage($request);
        $filters = $request->only([
            "warehouse_id",
            "item_id",
            "movement_type",
            "origin_types",
            "product_search",
            "date_from",
            "date_to",
        ]);

        $allWarehouses = ($filters["warehouse_id"] ?? null) === "all";
        $warehouseId = (int) ($filters["warehouse_id"] ?? 0);
        $filters["allowed_warehouse_ids"] = AccessScopeService::allowedIds(
            $this->getAuthUser(),
            AccessScopeService::WAREHOUSE
        );

        if($allWarehouses) {

            if(!RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.transfers.index")) {

                abort(403, "No tienes permiso para consultar los traslados.");

            }

            $filters["origin_types"] = ["transfer_out", "transfer_in"];
            unset($filters["warehouse_id"]);

        }elseif($warehouseId <= 0 || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            return response()->json(["data" => [], "total" => 0]);

        }

        return StockManagementService::getKardex(
            $filters,
            $perPage
        );

    }

    public function alerts(Request $request) {

        $filters = $request->validate([
            "warehouse_id" => ["required", "integer", "min:1"],
            "status" => ["nullable", "in:open,resolved"],
            "product_search" => ["nullable", "string", "max:100"],
            "minimum_status" => ["nullable", "in:configured,unconfigured"],
        ]);
        $warehouseId = (int) $filters["warehouse_id"];

        if($warehouseId <= 0 || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            return response()->json(["data" => [], "total" => 0]);

        }

        return StockManagementService::getStockAlerts(
            $filters,
            $this->inventoryPerPage($request)
        );

    }

    public function exportAlerts(Request $request): BinaryFileResponse|Response {

        $data = $request->validate([
            "warehouse_id" => ["required", "integer", "min:1"],
            "export" => ["required", "in:excel,pdf"],
            "product_search" => ["nullable", "string", "max:100"],
            "minimum_status" => ["nullable", "in:configured,unconfigured"],
        ]);
        $warehouse = $this->authorizedAlertWarehouse((int) $data["warehouse_id"]);
        $productSearch = trim((string) ($data["product_search"] ?? ""));
        $minimumStatus = (string) ($data["minimum_status"] ?? "");

        if($data["export"] === "excel") {

            return Excel::download(
                new StockAlertsExport((int) $warehouse->id, $productSearch, $minimumStatus),
                "alertas_stock_{$warehouse->id}_".now()->format("Ymd_His").".xlsx"
            );

        }

        $company = Company::query()->first(["legal_name", "commercial_name", "document_number"]);
        $query = StockManagementService::stockAlertsReportQuery((int) $warehouse->id, $productSearch, $minimumStatus);
        $alertCount = (clone $query)->reorder()->count();
        $binary = (new StockAlertsPdfRenderer())->render($warehouse, $company, $query->cursor(), $alertCount, now());
        $fileName = "alertas_stock_{$warehouse->id}_".now()->format("Ymd_His").".pdf";

        return response($binary, 200, [
            "Content-Type" => "application/pdf",
            "Content-Disposition" => "attachment; filename=\"{$fileName}\"",
            "Content-Length" => (string) strlen($binary),
        ]);

    }

    private function authorizedAlertWarehouse(int $warehouseId): Warehouse {

        $user = $this->getAuthUser();

        if(!RolePermissionService::canAccessRoute($user, "stocks_management.stock.index")
            || !RolePermissionService::canAccessRoute($user, "stocks_management.alerts.export")
            || !AccessScopeService::canAccess($user, AccessScopeService::WAREHOUSE, $warehouseId)) {
            abort(403, "No tienes permiso para descargar las alertas de este almacén.");
        }

        return Warehouse::query()->with("branch:id,name")->findOrFail($warehouseId);

    }

    public function guides(Request $request) {

        $filters = $request->only(["warehouse_id", "guide_type", "date_from", "date_to"]);
        $filters["allowed_warehouse_ids"] = AccessScopeService::allowedIds(
            $this->getAuthUser(),
            AccessScopeService::WAREHOUSE
        );

        return InventoryGuideService::query($filters)
            ->paginate($this->inventoryPerPage($request));

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

    public function operationTemplate(Request $request): BinaryFileResponse {

        $data = $request->validate([
            "warehouse_id" => ["required", "integer"],
            "operation_code" => ["required", "string", \Illuminate\Validation\Rule::in(InventoryOperationTemplate::codes())],
        ]);
        $warehouse = $this->authorizedOperationWarehouse((int) $data["warehouse_id"]);
        $operationCode = (string) $data["operation_code"];

        return Excel::download(
            new InventoryOperationTemplateExport((int) $warehouse->id, $operationCode),
            "plantilla_{$operationCode}_{$warehouse->id}.xlsx"
        );

    }

    private function stockFilters(Request $request): array {

        return $request->validate([
            "category_id" => ["nullable", "integer", "min:1"],
            "brand_id" => ["nullable", "integer", "min:1"],
            "minimum_status" => ["nullable", "in:configured,unconfigured"],
            "inactive_days" => ["nullable", "integer", "in:30,60,90"],
        ]);

    }

    public function previewOperation(PreviewInventoryOperationRequest $request): JsonResponse {

        $warehouse = $this->authorizedOperationWarehouse((int) $request->validated("warehouse_id"));
        $preview = new InventoryOperationPreviewImport((int) $warehouse->id, (string) $request->validated("operation_code"));

        try {

            Excel::import($preview, $request->file("file"));

        }catch(ValidationException $exception) {

            throw $exception;

        }catch(\Throwable $exception) {

            return response()->json([
                "bool" => false,
                "msg" => "No se pudo leer la plantilla. Descarga una nueva y comprueba el formato del archivo.",
            ], 422);

        }

        return response()->json([
            "bool" => true,
            "data" => ["items" => $preview->items()],
        ]);

    }

    private function authorizedOperationWarehouse(int $warehouseId): Warehouse {

        if(!RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.operations.store", "POST")) {

            abort(403, "No tienes permiso para registrar operaciones de inventario.");

        }

        $warehouse = StockManagementService::validateWarehouse($warehouseId);

        if(!$warehouse || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            abort(404, "El almacén seleccionado no está disponible.");

        }

        return $warehouse;

    }

    public function export(Request $request): BinaryFileResponse {

        $view = (string) $request->input("view", "stock");

        if(!in_array($view, ["stock", "kardex", "transfers", "valued", "guides"], true)) {

            abort(422, "El reporte solicitado no está disponible.");

        }

        if(!RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.{$view}.index")) {

            abort(403, "No tienes permiso para descargar este reporte.");

        }

        $filters = $request->only([
            "warehouse_id",
            "item_id",
            "movement_type",
            "origin_types",
            "product_search",
            "situation",
            "guide_type",
            "date_from",
            "date_to",
        ]);
        if($view === "stock") {
            $filters = array_merge($filters, $this->stockFilters($request));
        }
        $filters["allowed_warehouse_ids"] = AccessScopeService::allowedIds(
            $this->getAuthUser(),
            AccessScopeService::WAREHOUSE
        );

        if(($filters["warehouse_id"] ?? null) !== "all") {

            $warehouseId = (int) ($filters["warehouse_id"] ?? 0);
            $warehouse = StockManagementService::validateWarehouse($warehouseId);

            if(!$warehouse || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

                abort(404, "El almacén seleccionado no está disponible.");

            }

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

    private function inventoryPerPage(Request $request, int $default = self::MAX_LIST_PER_PAGE): int {

        return min(self::MAX_LIST_PER_PAGE, max(1, $this->getPerPage($request, $default)));

    }

    /**
     * Get translation namespace for stock management module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

}
