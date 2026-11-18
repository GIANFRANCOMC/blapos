<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Warehouses;

use App\Enums\System\Warehouses\{InventoryTransferStatus};
use App\Exports\System\Warehouses\{InventoryOperationTemplateExport, InventoryReportExport, StockAlertsExport};
use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Warehouses\{PreviewInventoryOperationRequest, StoreInventoryGuideRequest, StoreInventoryMovementRequest, StoreInventoryOperationRequest, StoreInventoryTransferRequest, UpdateStockLevelsRequest};
use App\Http\Requests\System\Warehouses\StoreTransferOperationRequest;
use App\Imports\System\Warehouses\{InventoryOperationPreviewImport};
use App\Models\System\Warehouses\{InventoryMovement, InventoryTransfer, Warehouse, WarehouseItem};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{Company};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Warehouses\Inventory\{InventoryGuideService, InventoryMovementService, InventoryTransferService};
use App\Services\System\Warehouses\StockManagement\{StockAlertsPdfRenderer, StockManagementConfigService, StockManagementService};
use App\Support\System\Warehouses\{InventoryOperationTemplate};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\{JsonResponse, Request, Response};
use Illuminate\Validation\{ValidationException};
use Illuminate\Validation\Rules\Enum;
use Maatwebsite\Excel\Facades\{Excel};
use Symfony\Component\HttpFoundation\{BinaryFileResponse};

class StockManagementController extends BaseController {

    private const MAX_LIST_PER_PAGE = 30;
    private const KARDEX_VIEWS = ["kardex", "valued"];
    private const CATALOG_FILTER_RULES = [
        "category_id" => ["nullable", "integer", "min:1"],
        "brand_id" => ["nullable", "integer", "min:1"],
    ];

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

        $params = StockManagementConfigService::getInitParams($page, $this->getUserId());
        if($page === "transfers") {
            unset($params->config->products);
            $params->config->movement_users = InventoryTransferService::getUsers($this->transferWarehouseScope());
            $params->config->can_operate_transfers = RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.transfers.store", "POST");
            $params->config->transfer_statuses = array_map(fn(InventoryTransferStatus $status) => [
                "code" => $status->value,
                "label" => $status->label(),
                "tone" => $status->tone(),
                "next" => $status->next()?->value,
                "can_cancel" => $status->canCancel(),
                "can_register" => $status->canRegister(),
            ], InventoryTransferStatus::cases());
        }
        $params->config->can_update_stock = RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.operations.store", "POST");

        if(in_array($page, self::KARDEX_VIEWS, true)) {
            $warehouseIds = $params->config->warehouses->records->pluck("id")->all();
            $params->config->movement_users = InventoryMovementService::getKardexUsers($warehouseIds);
        }

        return $params;

    }

    /**
     * Get paginated list of items with stock information
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $warehouseId = intval($request->input("warehouse_id"));
        $perPage = $this->inventoryPerPage($request);

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
                $this->inventoryPerPage($request),
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

            $filters["origin_types"] = InventoryMovement::TRANSFER_ORIGINS;
            unset($filters["warehouse_id"]);

        }elseif($warehouseId <= 0 || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            return response()->json(["data" => [], "total" => 0]);

        }else{

            $filters = array_merge($filters, $this->kardexFilters($request));

        }

        return StockManagementService::getKardex(
            $filters,
            $perPage
        );

    }

    public function traceability(Request $request) {

        abort_unless(RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.stock.index"), 403);
        $filters = $request->validate([
            "warehouse_id" => ["required", "integer", "min:1"],
            "item_id" => ["required", "integer", "min:1"],
        ]);
        $warehouseId = (int) $filters["warehouse_id"];
        if(!StockManagementService::validateWarehouse($warehouseId)
            || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            abort(404, "El almacén seleccionado no está disponible.");

        }
        abort_unless(Item::query()->whereKey((int) $filters["item_id"])->where("type", "product")->exists(), 404);

        return StockManagementService::getProductTraceability(
            $warehouseId,
            (int) $filters["item_id"],
            $this->inventoryPerPage($request)
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

    public function updateStockLevels(UpdateStockLevelsRequest $request): JsonResponse {

        $data = $request->validated();
        $warehouse = $this->authorizedOperationWarehouse((int) $data["warehouse_id"]);

        try {

            $stock = InventoryMovementService::updateStockLevels($data + ["user_id" => $this->getUserId()]);

            return response()->json(["bool" => true, "msg" => "Stock actualizado correctamente.", "data" => [
                "item_id" => (int) $stock->item_id,
                "quantity" => (float) $stock->quantity,
                "minimum_stock" => (float) $stock->minimum_stock,
            ]]);

        } catch(\DomainException $exception) {

            $current = WarehouseItem::query()->where("warehouse_id", $warehouse->id)->where("item_id", $data["item_id"])->first();

            return response()->json(["bool" => false, "msg" => $exception->getMessage(), "data" => [
                "quantity" => (float) ($current?->quantity ?? 0),
                "minimum_stock" => (float) ($current?->minimum_stock ?? 0),
            ]], $exception->getCode() === 409 ? 409 : 422);

        }

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

        return $request->validate(self::CATALOG_FILTER_RULES + [
            "minimum_status" => ["nullable", "in:configured,unconfigured"],
            "inactive_days" => ["nullable", "integer", "in:30,60,90"],
        ]);

    }

    private function kardexFilters(Request $request): array {

        return array_merge(
            $request->only("situation"),
            $this->stockFilters($request),
            $request->validate(["user_id" => ["nullable", "integer", "min:1"]])
        );

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
            "guide_type",
            "date_from",
            "date_to",
        ]);
        if($view === "stock") {
            $filters = array_merge($filters, $request->only("situation"), $this->stockFilters($request));
        }elseif($view === "transfers") {
            $filters = $this->transferFilters($request);
        }elseif(in_array($view, self::KARDEX_VIEWS, true)) {
            $filters = array_merge($filters, $this->kardexFilters($request));
        }
        $filters["allowed_warehouse_ids"] = AccessScopeService::allowedIds(
            $this->getAuthUser(),
            AccessScopeService::WAREHOUSE
        );

        if(($filters["warehouse_id"] ?? ($view === "transfers" ? "all" : null)) !== "all") {

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

    public function transfers(Request $request) {
        $filters = $this->transferFilters($request);
        $filters["allowed_warehouse_ids"] = $this->transferWarehouseScope();

        return InventoryTransferService::query($filters)->paginate($this->inventoryPerPage($request));
    }

    private function transferFilters(Request $request): array {
        return $request->validate([
            "user_id" => ["nullable", "integer", "min:1"],
            "warehouse_id" => ["nullable", "regex:/^(all|[1-9][0-9]*)$/"],
            "source_warehouse_id" => ["nullable", "integer", "min:1"],
            "destination_warehouse_id" => ["nullable", "integer", "min:1"],
            "item_id" => ["nullable", "integer", "min:1"],
            "product_search" => ["nullable", "string", "max:255"],
            "date_from" => ["nullable", "date_format:Y-m-d"],
            "date_to" => ["nullable", "date_format:Y-m-d"],
            "reference" => ["nullable", "string", "max:40"],
            "transfer_status" => ["nullable", new Enum(InventoryTransferStatus::class)],
        ]);
    }

    public function showTransfer(int $transfer): JsonResponse {
        return response()->json(["data" => $this->authorizedTransfer($transfer)]);
    }

    public function advanceTransfer(Request $request, int $transfer): JsonResponse {
        $data = $request->validate([
            "status" => ["required", new Enum(InventoryTransferStatus::class)],
            "expected_version" => ["required", "integer", "min:0"],
            "cancellation_reason" => ["nullable", "string", "max:255"],
        ]);
        $user = $this->getAuthUser();
        $record = $this->authorizedTransfer($transfer);
        abort_unless($this->transferWarehouseAllowed($record->source_warehouse_id)
            && RolePermissionService::canAccessRoute($user, "stocks_management.transfers.advance", "PATCH"), 403);

        try {
            $reason = trim($data["cancellation_reason"] ?? "");
            InventoryTransferService::advance(
                $transfer,
                InventoryTransferStatus::from($data["status"]),
                $this->getUserId(),
                (int) $data["expected_version"],
                $reason === "" ? null : $reason
            );
        }catch(\DomainException $exception) {
            return response()->json(["bool" => false, "msg" => $exception->getMessage()], $exception->getCode() === 409 ? 409 : 422);
        }

        return response()->json(["bool" => true, "data" => $this->authorizedTransfer($transfer)]);
    }

    public function printTransfer(int $transfer): Response {
        $record = $this->authorizedTransfer($transfer);
        $company = Company::query()->first(["legal_name", "commercial_name", "document_number"]);

        return Pdf::loadView("System.pdf.inventory.transfer", ["transfer" => $record, "company" => $company])
            ->setPaper("a4")
            ->stream("traslado-{$record->reference}.pdf");
    }

    private function authorizedTransfer(int $id): InventoryTransfer {
        $record = InventoryTransferService::withItems(InventoryTransferService::query([
            "allowed_warehouse_ids" => $this->transferWarehouseScope(),
        ]))->findOrFail($id);
        $user = $this->getAuthUser();
        $source = $this->transferWarehouseAllowed($record->source_warehouse_id);
        $destination = $this->transferWarehouseAllowed($record->destination_warehouse_id);
        $advance = $source && RolePermissionService::canAccessRoute($user, "stocks_management.transfers.advance", "PATCH");
        $record->setAttribute("can_dispatch", $advance && $record->status === InventoryTransferStatus::AwaitingDispatch);
        $record->setAttribute("can_cancel", $advance && $record->status->canCancel());
        $record->setAttribute("can_receive", $destination && $record->status->canReceive()
            && RolePermissionService::canAccessRoute($user, "stocks_management.transfers.receive", "POST"));
        $record->setAttribute("can_resolve", $source && $destination && $record->status->canReceive()
            && RolePermissionService::canAccessRoute($user, "stocks_management.transfers.resolve", "POST"));
        return $record;
    }

    private function transferWarehouseAllowed(int $id): bool {
        $ids = AccessScopeService::allowedIds($this->getAuthUser(), AccessScopeService::WAREHOUSE);
        return $ids === null || in_array($id, $ids, true);
    }

    public function receiveTransfer(StoreTransferOperationRequest $request, int $transfer): JsonResponse {
        return $this->processTransferOperation($request, $transfer, "receipt");
    }

    public function resolveTransfer(StoreTransferOperationRequest $request, int $transfer): JsonResponse {
        $data = $request->validated();
        abort_unless(in_array($data["kind"] ?? null, ["return", "loss"], true), 422, "Selecciona cómo resolver la diferencia.");
        return $this->processTransferOperation($request, $transfer, $data["kind"]);
    }

    private function processTransferOperation(StoreTransferOperationRequest $request, int $id, string $kind): JsonResponse {
        $record = $this->authorizedTransfer($id);
        $receipt = $kind === "receipt";
        abort_unless(RolePermissionService::canAccessRoute($this->getAuthUser(), "stocks_management.transfers.".($receipt ? "receive" : "resolve"), "POST")
            && $this->transferWarehouseAllowed($record->destination_warehouse_id)
            && ($receipt || $this->transferWarehouseAllowed($record->source_warehouse_id)), 403);
        try {
            $operation = InventoryTransferService::operate($id, $kind, $request->validated(), $this->getUserId());
            return response()->json(["bool" => true, "msg" => $receipt ? "Recepción registrada correctamente." : "Diferencia resuelta correctamente.",
                "operation_id" => $operation->id, "data" => $this->authorizedTransfer($id)]);
        }catch(\DomainException $exception) {
            return response()->json(["bool" => false, "msg" => $exception->getMessage()], $exception->getCode() === 409 ? 409 : 422);
        }
    }

    private function transferWarehouseScope(): ?array {
        $user = $this->getAuthUser();
        abort_unless(RolePermissionService::canAccessRoute($user, "stocks_management.transfers.index"), 403);

        return AccessScopeService::allowedIds($user, AccessScopeService::WAREHOUSE);
    }

    public function transferAvailability(Request $request): JsonResponse {

        $data = $request->validate([
            "warehouse_id" => ["required", "integer", "min:1"],
            "item_ids" => ["required", "array", "min:1", "max:100"],
            "item_ids.*" => ["required", "integer", "min:1", "distinct"],
        ]);
        $warehouseId = (int) $data["warehouse_id"];

        if(!StockManagementService::validateWarehouse($warehouseId)
            || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

            return $this->errorResponse("warehouse_not_available", [], 403);

        }

        return response()->json([
            "bool" => true,
            "data" => (object) StockManagementService::transferAvailability($warehouseId, $data["item_ids"])->all(),
        ]);

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

            $transfer = InventoryTransferService::register([
                "source_warehouse_id" => (int) $data["source_warehouse_id"],
                "destination_warehouse_id" => (int) $data["destination_warehouse_id"],
                "items" => $data["items"],
                "reason" => (string) $data["reason"],
                "status" => $data["status"] ?? InventoryTransferStatus::AwaitingDispatch->value,
                "user_id" => $this->getUserId(),
            ]);

            return response()->json([
                "bool" => true,
                "msg" => "Traslado registrado correctamente.",
                "data" => $transfer,
            ]);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    private function inventoryPerPage(Request $request): int {

        return min(self::MAX_LIST_PER_PAGE, max(1, $this->getPerPage($request, Utilities::$per_page_default)));

    }

    /**
     * Get translation namespace for stock management module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

}
