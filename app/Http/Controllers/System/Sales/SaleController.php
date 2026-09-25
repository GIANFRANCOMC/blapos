<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Sales;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Sales\{CancelSaleRequest, StoreSaleDeliveryRequest, StoreSaleRequest};
use App\Models\System\Sales\{SaleDelivery};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Sales\{SaleConfigService, SaleDeliveryService, SaleService};
use Illuminate\Http\{JsonResponse, Request};

class SaleController extends BaseController {
    /**
     * Translation namespace for sale module
     */
    private const TRANSLATION_NAMESPACE = "System.Sales.sale";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return SaleConfigService::getInitParams($this->getCompanyId(), $page, $this->getUserId());

    }

    /**
     * Get paginated list of sales with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = [
            "serie_id" => $request->input("serie_id"),
            "sequential" => $request->input("sequential"),
            "issue_date" => $request->input("issue_date"),
            "start_date" => $request->input("start_date"),
            "end_date" => $request->input("end_date"),
            "branch_id" => $request->input("branch_id"),
            "holder_id" => $request->input("holder_id"),
            "status" => $request->input("status"),
        ];

        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return SaleService::getPaginatedList(
            $this->getCompanyId(),
            $filters,
            $perPage,
            $this->getUserId()
        );

    }

    public function deliveries(Request $request) {

        $filters = [
            "branch_id" => $request->input("branch_id"),
            "warehouse_id" => $request->input("warehouse_id"),
            "holder_id" => $request->input("holder_id"),
            "delivery_status" => $request->input("delivery_status"),
            "search" => $request->input("search"),
        ];

        return SaleDeliveryService::paginatePending(
            $this->getCompanyId(),
            $filters,
            $this->getPerPage($request, Utilities::$per_page_default),
            $this->getUserId()
        );

    }

    /**
     * Display the sales index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Sales/sales/list");

    }

    /**
     * Show the form for creating a new sale
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create() {

        return view("System/general/Sales/sales/main");

    }

    public function deliveriesPage() {

        return view("System/general/Sales/sales/deliveries");

    }

    public function pos() {

        return view("System/general/Sales/pos/main");

    }

    /**
     * Store a newly created sale
     */
    public function store(StoreSaleRequest $request): JsonResponse {

        try {

            $data = $this->prepareSaleData($request);
            $sale = SaleService::create($data, $this->getCompanyId(), $this->getUserId());

            if(!Utilities::isDefined($sale)) {

                return $this->errorResponse("create_failed");

            }

            return $this->createdResponse($sale, "created", "sale");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Cancel the specified sale
     *
     * @param  int  $id Sale ID
     */
    public function cancel(CancelSaleRequest $request, int $id): JsonResponse {

        try {

            $sale = SaleService::findById($this->getCompanyId(), $id);

            if(!Utilities::isDefined($sale)) {

                return $this->notFoundResponse();

            }

            // Verify company ownership

            if($serie = $sale->serie) {

                $branch = $serie->branch;

                if(!Utilities::isDefined($branch)
                    || !AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::BRANCH, (int) $branch->id)) {

                    return $this->errorResponse("unauthorized", [], 403);

                }

            }

            $sale = SaleService::cancel($sale, $this->getCompanyId(), $this->getUserId());

            if(!Utilities::isDefined($sale)) {

                return $this->errorResponse("cancel_failed");

            }

            $stockRestored = (bool) ($sale->stock_restored_on_cancellation ?? false);

            $restorePolicyEnabled = (bool) ($sale->restore_stock_policy_enabled ?? false);
            $message = match (true) {
                $stockRestored => "Venta anulada correctamente. Los productos fueron devueltos al stock del almacén correspondiente.",
                $restorePolicyEnabled => "Venta anulada correctamente. La venta no contenía productos que devolver al inventario.",
                default => "Venta anulada correctamente. El stock no fue modificado. Si recibes productos devueltos, registra la devolución desde Inventario."
            };

            return response()->json([
                "bool" => true,
                "msg" => $message,
                "sale" => $sale,
            ]);

        }catch(\Exception $e) {

            return $this->handleException($e, "cancel");

        }

    }

    public function deliver(StoreSaleDeliveryRequest $request, int $id): JsonResponse {

        try {

            $delivery = SaleDelivery::query()
                ->find($id);

            if(!$delivery) {

                return $this->notFoundResponse();

            }

            $warehouseId = (int) ($request->warehouse_id ?? $delivery->warehouse_id);

            if(!AccessScopeService::canAccess($this->getAuthUser(), AccessScopeService::WAREHOUSE, $warehouseId)) {

                return $this->errorResponse("warehouse_not_available", [], 403);

            }

            $delivery = SaleDeliveryService::deliver(
                $delivery,
                $request->validated(),
                $this->getCompanyId(),
                $this->getUserId()
            );

            return response()->json([
                "bool" => true,
                "msg" => $delivery->status === "delivered"
                    ? "Entrega registrada correctamente. La venta quedó completamente entregada."
                    : "Entrega parcial registrada correctamente. Aún quedan productos pendientes.",
                "data" => $delivery,
            ]);

        }catch(\Throwable $e) {

            return response()->json([
                "bool" => false,
                "msg" => $e->getMessage(),
            ], 422);

        }

    }

    /**
     * Prepare sale data from request
     */
    private function prepareSaleData(StoreSaleRequest $request): array {

        return [
            "branch_id" => $request->branch_id,
            "serie_id" => $request->serie_id,
            "warehouse_id" => $request->warehouse_id,
            "cash_session_id" => $request->cash_session_id,
            "quotation_header_id" => $request->quotation_header_id,
            "service_session_id" => $request->service_session_id,
            "source_channel" => $request->source_channel ?? "sale",
            "holder_id" => $request->holder_id,
            "seller_id" => $request->seller_id,
            "currency_id" => $request->currency_id,
            "issue_date" => $request->issue_date,
            "delivery_method_id" => $request->delivery_method_id,
            "delivery_status" => $request->delivery_status,
            "delivery_observation" => $request->delivery_observation,
            "observation" => $request->observation,
            "taxes" => $request->taxes ?? [],
            "payments" => $request->payments ?? [],
            "payment_modality" => $request->payment_modality ?? "paid_now",
            "installment_count" => $request->installment_count,
            "first_due_date" => $request->first_due_date,
            "details" => $request->details,
        ];

    }

    /**
     * Get translation namespace for sale module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
