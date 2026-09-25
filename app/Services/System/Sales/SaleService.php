<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Customers\{Subscription};
use App\Models\System\Finance\{CashMovement, CashSession};
use App\Models\System\Organizations\{Serie, User};
use App\Models\System\Sales\{QuotationHeader, SaleBody, SaleDeliveryMethod, SaleHeader, SalePayment, SaleTax};
use App\Models\System\Warehouses\{InventoryMovement, Warehouse};
use App\Services\System\Catalogs\Recipes\{RecipeConsumptionService};
use App\Services\System\Customers\Loyalty\{CustomerLoyaltyPointService};
use App\Services\System\Customers\Tracking\{TrackingSubscriptionService};
use App\Services\System\Finance\{CommercialCreditAccountService, CommercialDocumentSettlementService};
use App\Services\System\Operations\{ServiceOperationService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use Exception;
use Illuminate\Support\Facades\{DB};
use stdClass;

/**
 * Service class for managing Sale operations
 * Handles business logic for creating, updating, and canceling sales
 */
class SaleService {
    /**
     * Translation namespace for sale module
     */
    private const TRANSLATION_NAMESPACE = "System.Sales.sale";

    private const CORRELATIVE_ISSUED = "issued";

    private const CORRELATIVE_CANCELED = "canceled";

    private const COMMISSION_TYPES = ["none", "percentage", "fixed"];

    /**
     * Get translation with fallback
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    private static function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback(self::TRANSLATION_NAMESPACE, $key, $replace);

    }

    /**
     * Calculate total from sale details
     *
     * @param  array  $details Sale details
     */
    private static function calculateTotal(array $details, int $companyId): float {

        $total = 0;

        foreach($details as $detail) {

            $total += Utilities::round(floatval($detail["quantity"]) * floatval($detail["price"]), null, $companyId);

        }

        return $total;

    }

    private static function normalizeCommissionType(?string $type): string {

        return in_array($type, self::COMMISSION_TYPES, true) ? $type : "none";

    }

    private static function normalizeCommissionValue(string $type, mixed $value): float {

        $normalized = is_numeric($value) ? max(0, (float) $value) : 0.0;

        if($type === "percentage") {

            return min($normalized, 100);

        }

        return $type === "fixed" ? $normalized : 0.0;

    }

    private static function calculateCommissionAmount(float $quantity, float $price, string $type, float $value, int $companyId): float {

        if($type === "percentage") {

            return Utilities::round(($quantity * $price) * ($value / 100), null, $companyId);

        }

        if($type === "fixed") {

            return Utilities::round($quantity * $value, null, $companyId);

        }

        return 0.0;

    }

    private static function resolveSellerId(array $data, int $companyId, int $userId): int {

        $sellerId = (int) ($data["seller_id"] ?? $userId);

        $exists = User::query()
            ->where("status", "active")
            ->whereKey($sellerId)
            ->exists();

        if(!$exists) {

            throw new Exception("El vendedor seleccionado no está disponible para esta empresa.");

        }

        return $sellerId;

    }

    private static function normalizeCommissionDetails(array $details, int $companyId): array {

        $items = Item::query()
            ->whereIn("id", collect($details)->pluck("item_id")->filter()->unique()->values())
            ->get(["id", "commission_rate", "commission_type", "commission_value"])
            ->keyBy("id");

        return array_map(function(array $detail) use ($items, $companyId) {

            $item = $items->get((int) ($detail["item_id"] ?? 0));
            $fallbackRate = (float) ($item?->commission_rate ?? 0);
            $type = self::normalizeCommissionType($detail["commission_type"] ?? $item?->commission_type ?? null);

            if($type === "none" && $fallbackRate > 0) {

                $type = "percentage";

            }

            $value = self::normalizeCommissionValue(
                $type,
                $detail["commission_value"] ?? $item?->commission_value ?? $fallbackRate
            );

            $detail["commission_type"] = $type;
            $detail["commission_value"] = $value;
            $detail["commission_amount"] = self::calculateCommissionAmount(
                (float) ($detail["quantity"] ?? 0),
                (float) ($detail["price"] ?? 0),
                $type,
                $value,
                $companyId
            );

            return $detail;

        }, $details);

    }

    private static function lockCatalogItemsForSale(array $details, int $companyId) {

        $itemIds = collect($details)
            ->pluck("item_id")
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        return Item::query()
            ->whereIn("id", $itemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy("id");

    }

    private static function validateSaleCatalogItems(array $details, $items): void {

        foreach($details as $detail) {

            $item = $items->get((int) ($detail["item_id"] ?? 0));

            if(!$item) {

                throw new Exception("Uno de los ítems seleccionados no pertenece a la empresa.");

            }

            if(!$item->isAvailableForSale()) {

                throw new Exception("{$item->name} no está disponible para la venta.");

            }

            if($item->hasCapacityControl()) {

                $requiredCapacity = self::capacityQuantity($detail["quantity"] ?? 0);

                if($item->availableCapacity() < $requiredCapacity) {

                    throw new Exception("{$item->name} no tiene cupos disponibles suficientes.");

                }

            }

        }

    }

    private static function normalizeTaxFlags(array $details, $items): array {

        return array_map(function(array $detail) use ($items) {

            $item = $items->get((int) ($detail["item_id"] ?? 0));
            $igvExempt = filter_var($item?->igv_exempt ?? false, FILTER_VALIDATE_BOOL);

            $detail["igv_exempt"] = $igvExempt;
            $detail["price_includes_tax"] = $igvExempt
                ? false
                : filter_var($item?->price_includes_tax ?? true, FILTER_VALIDATE_BOOL);

            return $detail;

        }, $details);

    }

    private static function capacityQuantity(mixed $quantity): int {

        return max(1, (int) ceil((float) $quantity));

    }

    private static function consumeItemCapacity(?Item $item, SaleBody $saleBody, int $userId): void {

        if(!$item
            || !$item->hasCapacityControl()
            || !in_array($saleBody->type, ["service", "subscription"], true)) {

            return;

        }

        $item->capacity_used = max(0, (int) $item->capacity_used) + self::capacityQuantity($saleBody->quantity);

        $item->updated_at = now();
        $item->updated_by = $userId;
        $item->save();

    }

    private static function restoreItemCapacityForCanceledSale($positions, int $companyId, int $userId): void {

        $capacityPositions = $positions->filter(fn($position) => in_array($position->type, ["service", "subscription"], true));

        if($capacityPositions->isEmpty()) {

            return;

        }

        $items = Item::query()
            ->whereIn("id", $capacityPositions->pluck("item_id")->filter()->unique()->values())
            ->lockForUpdate()
            ->get()
            ->keyBy("id");

        foreach($capacityPositions as $position) {

            $item = $items->get((int) $position->item_id);

            if(!$item || !$item->hasCapacityControl()) {

                continue;

            }

            $item->capacity_used = max(0, (int) $item->capacity_used - self::capacityQuantity($position->quantity));

            $item->updated_at = now();
            $item->updated_by = $userId;
            $item->save();

        }

    }

    /**
     * Prepare sale body extras for subscription
     *
     * @param  array  $detail Sale detail
     */
    private static function prepareSubscriptionExtras(array $detail): stdClass {

        $extras = (object) ($detail["extras"] ?? []);

        if(isset($detail["extras"])) {

            $extras->duration_type = $detail["extras"]["duration_type"] ?? null;
            $extras->duration_value = $detail["extras"]["duration_value"] ?? null;
            $extras->start_date = isset($detail["extras"]["start_date"]) ? str_replace("T", " ", $detail["extras"]["start_date"]) : null;
            $extras->end_date = isset($detail["extras"]["end_date"]) ? str_replace("T", " ", $detail["extras"]["end_date"]) : null;
            $extras->set_end_of_day = $detail["extras"]["set_end_of_day"] ?? false;
            $extras->force = $detail["extras"]["force"] ?? false;
            $extras->observation = $detail["extras"]["observation"] ?? "";

        }

        return $extras;

    }

    /**
     * Create sale body from detail
     *
     * @param  SaleHeader  $saleHeader Sale header instance
     * @param  array  $detail Sale detail data
     * @param  int  $userId User ID
     */
    private static function createSaleBody(SaleHeader $saleHeader, array $detail, int $userId): SaleBody {

        $extras = new stdClass();

        if(in_array($detail["type"], ["subscription"])) {

            $extras = self::prepareSubscriptionExtras($detail);

        }

        $saleBody = new SaleBody();

        $saleBody->sale_header_id = $saleHeader->id;
        $saleBody->item_id = $detail["item_id"];
        $saleBody->currency_id = $detail["currency_id"];
        $saleBody->name = $detail["name"];
        $saleBody->quantity = $detail["quantity"];
        $saleBody->price = $detail["price"];
        $saleBody->igv_exempt = filter_var($detail["igv_exempt"] ?? false, FILTER_VALIDATE_BOOL);
        $saleBody->price_includes_tax = $saleBody->igv_exempt
            ? false
            : filter_var($detail["price_includes_tax"] ?? true, FILTER_VALIDATE_BOOL);

        $saleBody->total = Utilities::round((floatval($saleBody->quantity) * floatval($saleBody->price)));
        $saleBody->commission_type = $detail["commission_type"] ?? "none";
        $saleBody->commission_value = $detail["commission_value"] ?? 0;
        $saleBody->commission_amount = $detail["commission_amount"] ?? 0;
        $saleBody->customer_id = $saleBody->type === "subscription"
            ? (int) ($detail["customer_id"] ?? $saleHeader->holder_id)
            : $saleHeader->holder_id;

        $saleBody->type = $detail["type"];
        $saleBody->observation = $detail["observation"] ?? "";
        $saleBody->extras = json_encode($extras);
        $saleBody->status = "active";
        $saleBody->created_at = now();
        $saleBody->created_by = $userId;
        $saleBody->save();

        return $saleBody;

    }

    /**
     * Update warehouse inventory for product sale
     *
     * @param  Warehouse  $warehouse Warehouse instance
     * @param  SaleBody  $saleBody Sale body instance
     * @param  int  $userId User ID
     * @return void
     */
    public static function applyInventoryExit(
        Warehouse $warehouse,
        SaleBody $saleBody,
        array $detail,
        int $userId,
        string $originType = InventoryMovementService::ORIGIN_SALE,
        string $reason = "Salida generada por venta.",
        array $metadata = []
    ): ?InventoryMovement {

        $companyId = app(\App\Services\System\Tenancy\TenantCompanyContext::class)->id();
        $allowNegativeStock = (bool) CompanySettingService::value(
            $companyId,
            CompanySettingService::INVENTORY_POLICIES,
            "allow_negative_stock_on_sale",
            false
        );

        if(RecipeConsumptionService::consume(
            $warehouse,
            $saleBody,
            $detail,
            $companyId,
            $userId,
            $allowNegativeStock
        )) {

            return null;

        }

        if(!in_array($saleBody->type, ["product"])) {

            return null;

        }

        $quantity = Utilities::round((float) ($detail["quantity"] ?? $saleBody->quantity), null, $companyId);

        return InventoryMovementService::apply([
            "warehouse_id" => (int) $warehouse->id,
            "item_id" => (int) $saleBody->item_id,
            "user_id" => $userId,
            "movement_type" => InventoryMovementService::TYPE_EXIT,
            "origin_type" => $originType,
            "origin_id" => (int) $saleBody->id,
            "quantity" => $quantity,
            "reason" => $reason,
            "allow_negative" => $allowNegativeStock,
            "metadata" => $metadata + [
                "sale_header_id" => (int) $saleBody->sale_header_id,
            ],
        ]);

    }

    /**
     * Create subscription from sale body
     *
     * @param  SaleHeader  $saleHeader Sale header instance
     * @param  SaleBody  $saleBody Sale body instance
     * @param  array  $detail Sale detail data
     * @param  int  $companyId Company ID
     * @param  int  $branchId Branch ID
     * @param  int  $userId User ID
     */
    private static function createSubscription(SaleHeader $saleHeader, SaleBody $saleBody, array $detail, int $companyId, int $branchId, int $userId): ?Subscription {

        if(!in_array($saleBody->type, ["subscription"])) {

            return null;

        }

        $extras = self::prepareSubscriptionExtras($detail);

        $subscriptionCustomerId = (int) ($detail["customer_id"] ?? $saleHeader->holder_id);

        self::validateSubscriptionCustomer($companyId, $subscriptionCustomerId);

        TrackingSubscriptionService::assertDatesAvailable(
            $companyId,
            $branchId,
            $subscriptionCustomerId,
            (string) $extras->start_date,
            (string) $extras->end_date,
            (bool) $extras->force
        );

        $subscription = new Subscription();
        $subscription->branch_id = $branchId;
        $subscription->sale_header_id = $saleHeader->id;
        $subscription->sale_body_id = $saleBody->id;
        $subscription->customer_id = $subscriptionCustomerId;
        $subscription->duration_type = $extras->duration_type;
        $subscription->duration_value = $extras->duration_value;
        $subscription->start_date = $extras->start_date;
        $subscription->end_date = $extras->end_date;
        $subscription->set_end_of_day = $extras->set_end_of_day;
        $subscription->force = $extras->force;
        $subscription->attendance_limit_per_day = max(1, (int) ($detail["attendance_limit_per_day"] ?? 1));
        $subscription->observation = $extras->observation;
        $subscription->motive = null;
        $subscription->type = "sale";
        $subscription->status = "active";
        $subscription->created_at = now();
        $subscription->created_by = $userId;
        $subscription->save();

        if((bool) CompanySettingService::value(
            $companyId,
            CompanySettingService::SUBSCRIPTIONS,
            "send_welcome_email_on_sale",
            true
        )) {

            TrackingSubscriptionService::queueWelcomeEmail(
                $subscription,
                $subscription->customer,
                Item::query()
                    ->whereKey((int) $saleBody->item_id)
                    ->first(),
                $userId
            );

        }

        return $subscription;

    }

    private static function validateSubscriptionCustomer(int $companyId, int $customerId): void {

        $exists = \App\Models\System\Customers\Customer::query()
            ->where("status", "active")
            ->whereKey($customerId)
            ->exists();

        if(!$exists) {

            throw new Exception("El cliente seleccionado para la membresía no está activo o no pertenece a la empresa.");

        }

    }

    private static function saleRequiresWarehouse(array $details): bool {

        return collect($details)->contains(function($detail) {

            return in_array(strtolower((string) ($detail["type"] ?? "")), ["product"], true);

        });

    }

    private static function resolveWarehouse(array $data, int $companyId): Warehouse {

        $warehouseQuery = Warehouse::query()
            ->with("branch")
            ->where("branch_id", $data["branch_id"])
            ->whereHas("branch", function($query) {

                $query->where("status", "active");

            })
            ->where("status", "active");

        if(Utilities::isDefined($data["warehouse_id"] ?? null)) {

            $warehouse = (clone $warehouseQuery)
                ->where("id", $data["warehouse_id"])
                ->first();

            if(!$warehouse) {

                throw new Exception("El almacén seleccionado no está activo o no pertenece a la sucursal de la venta.");

            }

            return $warehouse;

        }

        $warehouses = $warehouseQuery->get();

        if($warehouses->count() === 1) {

            return $warehouses->first();

        }

        if($warehouses->isEmpty()) {

            throw new Exception("La sucursal no tiene un almacén activo. Crea o activa un almacén antes de registrar la venta.");

        }

        throw new Exception("Selecciona el almacén que será afectado por la venta.");

    }

    private static function resolveDeliveryMethodId(array $data, int $companyId, bool $requiresPhysicalDelivery): ?int {

        if(!$requiresPhysicalDelivery) {

            return null;

        }

        $methodId = (int) ($data["delivery_method_id"] ?? 0);

        if($methodId <= 0) {

            throw new \DomainException("Selecciona una modalidad de entrega activa.");

        }

        $method = SaleDeliveryMethod::query()
            ->where("status", "active")
            ->find($methodId);

        if(!$method) {

            throw new \DomainException("Selecciona una modalidad de entrega activa.");

        }

        return (int) $method->id;

    }

    private static function resolveCashSession(
        array $data,
        int $companyId,
        int $userId
    ): ?CashSession {

        $cashSessionId = (int) ($data["cash_session_id"] ?? 0);
        $required = (bool) CompanySettingService::value(
            $companyId,
            CompanySettingService::CASH,
            "require_open_session_on_sale",
            false
        );

        if($cashSessionId <= 0) {

            if($required) {

                throw new Exception("Abre una caja de la sucursal antes de registrar la venta.");

            }

            return null;

        }

        $session = CashSession::query()
            ->with("register")
            ->where("branch_id", (int) $data["branch_id"])
            ->where("status", "open")
            ->find($cashSessionId);

        if(!$session
            || !AccessScopeService::canAccess(
                User::query()->findOrFail($userId),
                AccessScopeService::CASH_REGISTER,
                (int) $session->cash_register_id
            )) {

            throw new Exception("La caja seleccionada no está abierta, no pertenece a la sucursal o no está autorizada para el usuario.");

        }

        return $session;

    }

    private static function createCashMovements(SaleHeader $saleHeader, $paymentLines, int $companyId, int $branchId, int $userId): void {

        if(!Utilities::isDefined($saleHeader->cash_session_id) || $paymentLines->isEmpty()) {

            return;

        }

        CashMovement::insert($paymentLines
            ->map(function($payment) use ($saleHeader, $branchId, $userId) {

                return [
                    "branch_id" => $branchId,
                    "cash_session_id" => $saleHeader->cash_session_id,
                    "payment_method_id" => $payment["payment_method_id"] ?? null,
                    "user_id" => $userId,
                    "movement_type" => "sale",
                    "origin_type" => "sale",
                    "origin_id" => $saleHeader->id,
                    "amount" => $payment["amount"] ?? 0,
                    "reference" => $payment["reference"] ?? null,
                    "note" => $payment["note"] ?? null,
                    "occurred_at" => now(),
                    "status" => "active",
                    "created_at" => now(),
                    "created_by" => $userId,
                ];

            })
            ->all());

    }

    private static function validateSerieBelongsToBranch(int $serieId, int $branchId): void {

        $exists = Serie::query()
            ->where("id", $serieId)
            ->where("branch_id", $branchId)
            ->where("status", "active")
            ->exists();

        if(!$exists) {

            $hasActiveSeries = Serie::query()
                ->where("branch_id", $branchId)
                ->where("status", "active")
                ->exists();

            if(!$hasActiveSeries) {

                throw new Exception("La sucursal no tiene una serie activa. Crea o activa una serie antes de registrar la venta.");

            }

            throw new Exception("El comprobante seleccionado no está activo o no pertenece a la sucursal de la venta.");

        }

    }

    private static function recordCorrelativeMovement(
        SaleHeader $saleHeader,
        string $action,
        int $companyId,
        int $userId,
        string $source = "sale"
    ): void {

        DB::table("series_correlative_movements")->insert([
            "serie_id" => (int) $saleHeader->serie_id,
            "sale_header_id" => (int) $saleHeader->id,
            "user_id" => $userId,
            "sequential" => (int) $saleHeader->sequential,
            "action" => $action,
            "source" => in_array($source, ["sale", "pos"], true) ? $source : "sale",
            "note" => $action === self::CORRELATIVE_CANCELED
                ? "Correlativo conservado en el historial por anulación de la venta."
                : "Correlativo asignado al registrar la venta.",
            "metadata" => json_encode([
                "sale_status" => (string) $saleHeader->status,
            ]),
            "occurred_at" => now(),
            "created_at" => now(),
        ]);

    }

    /**
     * Create a new sale
     *
     * @param  array  $data Sale data from request
     * @param  int  $companyId Company that owns the sale
     * @param  int  $userId User ID creating the sale
     * @return SaleHeader|null Created sale header instance or null on failure
     *
     * @throws Exception
     */
    public static function create(array $data, int $companyId, int $userId): ?SaleHeader {

        $saleHeader = null;

        DB::transaction(function() use ($data, $companyId, $userId, &$saleHeader) {

            $cashSession = self::resolveCashSession($data, (int) $companyId, (int) $userId);
            $sellerId = self::resolveSellerId($data, (int) $companyId, (int) $userId);
            self::validateSerieBelongsToBranch((int) $data["serie_id"], (int) $data["branch_id"]);
            $data["details"] = self::normalizeCommissionDetails($data["details"], (int) $companyId);
            $catalogItems = self::lockCatalogItemsForSale($data["details"], (int) $companyId);
            self::validateSaleCatalogItems($data["details"], $catalogItems);
            $data["details"] = self::normalizeTaxFlags($data["details"], $catalogItems);
            $requiresWarehouse = self::saleRequiresWarehouse($data["details"]);
            $warehouse = $requiresWarehouse ? self::resolveWarehouse($data, (int) $companyId) : null;
            $usesSaleDeliveryFlow = SaleDeliveryPolicy::usesManagedDelivery(
                (string) ($data["source_channel"] ?? "sale"),
                $requiresWarehouse
            );

            $deliveryMethodId = self::resolveDeliveryMethodId($data, (int) $companyId, $usesSaleDeliveryFlow);
            $deliveryStatus = SaleDeliveryPolicy::initialStatus(
                $data["delivery_status"] ?? null,
                $usesSaleDeliveryFlow
            );

            // Get new sequential number

            $newSequential = SaleHeader::getNewSequential($data["serie_id"]);

            if($newSequential <= 0) {

                throw new Exception("No se pudo generar el número secuencial.");

            }

            // Calculate totals
            $grossSubtotal = self::calculateTotal($data["details"], (int) $companyId);

            $commissionTotal = Utilities::round(array_reduce($data["details"], function($carry, $detail) {

                return $carry + (float) ($detail["commission_amount"] ?? 0);

            }, 0), null, (int) $companyId);

            $selectedTaxIds = collect($data["taxes"] ?? [])
                ->pluck("tax_id")
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $selectedTaxQuantities = collect($data["taxes"] ?? [])
                ->filter(fn($tax) => !empty($tax["tax_id"]))
                ->mapWithKeys(fn($tax) => [(int) $tax["tax_id"] => (float) ($tax["quantity"] ?? 1)])
                ->all();

            $taxLines = CommercialDocumentSettlementService::saleTaxes(
                (int) $companyId,
                $data["details"],
                (int) $userId,
                $selectedTaxIds,
                $selectedTaxQuantities
            );

            $taxTotal = Utilities::round((float) $taxLines->sum("amount"), null, (int) $companyId);
            $taxImpactTotal = Utilities::round((float) $taxLines->sum("_total_impact"), null, (int) $companyId);
            $includedTaxTotal = Utilities::round($taxTotal - $taxImpactTotal, null, (int) $companyId);
            $subtotal = Utilities::round($grossSubtotal - $includedTaxTotal, null, (int) $companyId);
            $baseTotal = Utilities::round($grossSubtotal + $taxImpactTotal, null, (int) $companyId);
            $defaultPaymentModality = (string) CompanySettingService::value(
                (int) $companyId,
                CompanySettingService::SALES,
                "default_payment_modality",
                CommercialCreditAccountService::PAID_NOW
            );

            $paymentModality = CommercialCreditAccountService::normalizePaymentModality(
                $data["payment_modality"] ?? null,
                $defaultPaymentModality
            );

            $paymentLines = CommercialDocumentSettlementService::payments(
                (int) $companyId,
                "sale",
                (float) $baseTotal,
                $data["payments"] ?? [],
                (int) $userId,
                $paymentModality === CommercialCreditAccountService::PAID_NOW
            );

            $paidAmount = Utilities::round((float) $paymentLines->sum("amount"), null, (int) $companyId);
            $financedPrincipal = $paymentModality === CommercialCreditAccountService::INSTALLMENTS
                ? Utilities::round($baseTotal - $paidAmount, null, (int) $companyId)
                : 0.0;

            if($paymentModality === CommercialCreditAccountService::INSTALLMENTS && $financedPrincipal <= 0) {

                throw new \DomainException("El crédito en cuotas requiere un saldo pendiente por financiar.");

            }

            $installmentExtraPercentage = $paymentModality === CommercialCreditAccountService::INSTALLMENTS && $financedPrincipal > 0
                ? (float) CompanySettingService::value(
                    (int) $companyId,
                    CompanySettingService::SALES,
                    "installment_extra_percentage",
                    0
                )
                : 0.0;

            $installmentExtraAmount = Utilities::round($financedPrincipal * ($installmentExtraPercentage / 100), null, (int) $companyId);
            $total = Utilities::round($baseTotal + $installmentExtraAmount, null, (int) $companyId);
            $balanceDue = Utilities::round($total - $paidAmount, null, (int) $companyId);
            $paymentStatus = CommercialCreditAccountService::paymentStatus((float) $total, (float) $paidAmount, (int) $companyId);

            // Create sale header
            $saleHeader = new SaleHeader();

            $saleHeader->serie_id = $data["serie_id"];
            $saleHeader->sequential = $newSequential;
            $saleHeader->holder_id = $data["holder_id"];
            $saleHeader->seller_id = $sellerId;
            $saleHeader->currency_id = $data["currency_id"];
            $saleHeader->warehouse_id = $warehouse?->id;
            $saleHeader->delivery_method_id = $deliveryMethodId;
            $saleHeader->cash_session_id = $cashSession?->id;
            $saleHeader->quotation_header_id = $data["quotation_header_id"] ?? null;
            $saleHeader->issue_date = $data["issue_date"];
            // Campo legado conservado para compatibilidad; el estado es la fuente operativa.
            $saleHeader->delivery_mode = SaleDeliveryPolicy::legacyMode($deliveryStatus);

            $saleHeader->delivery_status = $deliveryStatus;
            $saleHeader->delivered_at = $deliveryStatus === "delivered" ? now() : null;
            $saleHeader->delivered_by = $deliveryStatus === "delivered" ? $userId : null;
            $saleHeader->delivery_observation = $data["delivery_observation"] ?? null;
            $saleHeader->payment_modality = $paymentModality;
            $saleHeader->installment_extra_percentage = $installmentExtraPercentage;
            $saleHeader->installment_extra_amount = $installmentExtraAmount;
            $saleHeader->subtotal = $subtotal;
            $saleHeader->tax = $taxTotal;
            $saleHeader->commission_total = $commissionTotal;
            $saleHeader->total = $total;
            $saleHeader->paid_amount = $paidAmount;
            $saleHeader->balance_due = $balanceDue;
            $saleHeader->payment_status = $paymentStatus;
            $saleHeader->observation = $data["observation"] ?? "";
            $saleHeader->status = "active";
            $saleHeader->created_at = now();
            $saleHeader->created_by = $userId;
            $saleHeader->save();

            self::recordCorrelativeMovement(
                $saleHeader,
                self::CORRELATIVE_ISSUED,
                (int) $companyId,
                (int) $userId,
                (string) ($data["source_channel"] ?? "sale")
            );

            if($taxLines->isNotEmpty()) {

                SaleTax::insert($taxLines
                    ->map(function($tax) use ($saleHeader) {

                        unset($tax["_total_impact"]);

                        return ["sale_header_id" => $saleHeader->id] + $tax;

                    })
                    ->all());

            }

            if($paymentLines->isNotEmpty()) {

                SalePayment::insert($paymentLines
                    ->map(fn($payment) => ["sale_header_id" => $saleHeader->id] + $payment)
                    ->all());

                self::createCashMovements($saleHeader, $paymentLines, (int) $companyId, (int) $data["branch_id"], (int) $userId);

            }

            CommercialCreditAccountService::createReceivable(
                $saleHeader,
                $paymentLines,
                (int) ($data["installment_count"] ?? 1),
                $data["first_due_date"] ?? null,
                (int) $userId
            );

            $saleBodies = collect();

            // Create sale bodies and process details

            foreach($data["details"] as $detail) {

                $saleBody = self::createSaleBody($saleHeader, $detail, $userId);
                $saleBodies->push($saleBody);

                if($warehouse && SaleDeliveryPolicy::shouldExitInventory($deliveryStatus, $requiresWarehouse)) {

                    self::applyInventoryExit($warehouse, $saleBody, $detail, $userId);

                }

                // Create subscription for subscription items
                self::createSubscription($saleHeader, $saleBody, $detail, $companyId, $data["branch_id"], $userId);
                self::consumeItemCapacity($catalogItems->get((int) $detail["item_id"]), $saleBody, $userId);

            }

            if($warehouse && SaleDeliveryPolicy::shouldCreatePendingDelivery($deliveryStatus, $requiresWarehouse)) {

                SaleDeliveryService::createPendingForSale($saleHeader, $saleBodies, (int) $warehouse->id, (int) $userId);

            }

            CustomerLoyaltyPointService::awardForSale(
                $saleHeader,
                $saleBodies,
                (int) $companyId,
                (int) $userId
            );

            if(Utilities::isDefined($data["service_session_id"] ?? null)) {

                ServiceOperationService::attachSale(
                    (int) $companyId,
                    (int) $userId,
                    (int) $data["service_session_id"],
                    (int) $saleHeader->id
                );

            }

            if(Utilities::isDefined($data["quotation_header_id"] ?? null)) {

                QuotationHeader::query()
                    ->whereKey((int) $data["quotation_header_id"])
                    ->whereIn("status", ["draft", "sent", "accepted"])
                    ->update([
                        "sale_header_id" => $saleHeader->id,
                        "status" => "converted",
                        "converted_at" => now(),
                        "converted_by" => $userId,
                        "updated_at" => now(),
                        "updated_by" => $userId,
                    ]);

                SaleConfigService::clearCache("main");

            }

        });

        return $saleHeader;

    }

    /**
     * Cancel a sale
     *
     * @param  SaleHeader  $saleHeader Sale header instance
     * @param  int  $companyId Company that owns the sale
     * @param  int  $userId User ID canceling the sale
     * @return SaleHeader Updated sale header instance
     *
     * @throws Exception
     */
    public static function cancel(SaleHeader $saleHeader, int $companyId, int $userId): SaleHeader {

        $stockRestored = false;
        $restoreStockPolicyEnabled = false;

        DB::transaction(function() use (
            $saleHeader,
            $companyId,
            $userId,
            &$stockRestored,
            &$restoreStockPolicyEnabled
        ) {

            $restoreStockPolicyEnabled = (bool) CompanySettingService::value(
                $companyId,
                CompanySettingService::INVENTORY_POLICIES,
                "restore_stock_on_sale_cancellation",
                false
            );

            if(!in_array($saleHeader->status, ["active"])) {

                throw new Exception("La venta no puede ser anulada.");

            }

            $saleHeader->loadMissing(
                $restoreStockPolicyEnabled
                    ? ["serie.branch.warehouses", "allPositions"]
                    : ["allPositions"]
            );

            $allPositions = $saleHeader->allPositions;
            SaleDeliveryService::cancelForSale($saleHeader, (int) $companyId, (int) $userId);
            self::restoreItemCapacityForCanceledSale($allPositions, (int) $companyId, (int) $userId);
            CustomerLoyaltyPointService::reverseForCanceledSale($saleHeader, (int) $companyId, (int) $userId);

            $productPositions = $allPositions->where("type", "product");
            $fallbackWarehouse = $restoreStockPolicyEnabled && $productPositions->isNotEmpty()
                ? $saleHeader->serie?->branch?->warehouses?->first()
                : null;

            $saleMovements = $restoreStockPolicyEnabled && $allPositions->isNotEmpty()
                ? InventoryMovement::query()
                    ->whereIn("origin_type", [
                        InventoryMovementService::ORIGIN_SALE,
                        InventoryMovementService::ORIGIN_SALE_DELIVERY,
                        InventoryMovementService::ORIGIN_RECIPE_SALE,
                    ])
                    ->whereIn("origin_id", $allPositions->pluck("id"))
                    ->orderByDesc("id")
                    ->get([
                        "id",
                        "warehouse_id",
                        "item_id",
                        "origin_id",
                        "origin_type",
                        "quantity_change",
                        "unit_cost",
                    ])
                    ->groupBy("origin_id")
                : collect();

            $inventoryPositions = $allPositions->filter(fn($position) => $position->type === "product" || $saleMovements->has($position->id)
            );

            if($restoreStockPolicyEnabled
                && $productPositions->isNotEmpty()
                && !$fallbackWarehouse
                && $productPositions->contains(fn($position) => !$saleMovements->has($position->id))) {

                throw new Exception("No se encontró el almacén asociado a la venta.");

            }

            if($restoreStockPolicyEnabled && $inventoryPositions->isNotEmpty()) {

                foreach($inventoryPositions as $saleBody) {

                    $positionMovements = $saleMovements->get($saleBody->id, collect());

                    if($positionMovements->isEmpty()) {

                        if($saleBody->type !== "product") {

                            continue;

                        }

                        $positionMovements = collect([(object) [
                            "warehouse_id" => $fallbackWarehouse?->id,
                            "item_id" => $saleBody->item_id,
                            "quantity_change" => -(float) $saleBody->quantity,
                            "unit_cost" => 0,
                            "origin_type" => InventoryMovementService::ORIGIN_SALE,
                        ]]);

                    }

                    foreach($positionMovements as $movement) {

                        if((int) $movement->warehouse_id <= 0) {

                            throw new Exception("No se encontró el almacén original de uno de los productos.");

                        }

                        InventoryMovementService::apply([
                            "warehouse_id" => (int) $movement->warehouse_id,
                            "item_id" => (int) $movement->item_id,
                            "user_id" => $userId,
                            "movement_type" => InventoryMovementService::TYPE_ENTRY,
                            "origin_type" => InventoryMovementService::ORIGIN_SALE_CANCELLATION,
                            "origin_id" => (int) $saleBody->id,
                            "quantity" => abs((float) $movement->quantity_change),
                            "unit_cost" => (float) $movement->unit_cost,
                            "reason" => "Devolución automática por anulación de venta.",
                            "metadata" => [
                                "sale_header_id" => (int) $saleHeader->id,
                                "automatic_return" => true,
                                "original_origin_type" => $movement->origin_type,
                            ],
                        ]);

                    }

                }

                $stockRestored = true;

            }

            // Update sale header
            $saleHeader->status = "canceled";

            $saleHeader->delivery_status = "canceled";
            $saleHeader->updated_at = now();
            $saleHeader->updated_by = $userId;
            $saleHeader->canceled_at = now();
            $saleHeader->canceled_by = $userId;
            $saleHeader->save();

            $correlativeSource = (string) (
                DB::table("series_correlative_movements")
                    ->where("sale_header_id", $saleHeader->id)
                    ->where("action", self::CORRELATIVE_ISSUED)
                    ->value("source")
                ?? "sale"
            );

            self::recordCorrelativeMovement(
                $saleHeader,
                self::CORRELATIVE_CANCELED,
                $companyId,
                (int) $userId,
                $correlativeSource
            );

            // Cancel sale bodies
            SaleBody::where("sale_header_id", $saleHeader->id)
                ->whereIn("status", ["active"])
                ->update([
                    "status" => "canceled",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                    "canceled_at" => now(),
                    "canceled_by" => $userId,
                ]);

            // Cancel subscriptions

            $motive = "Por la anulación de la venta.";

            Subscription::query()
                ->where("sale_header_id", $saleHeader->id)
                ->whereIn("type", ["sale"])
                ->whereIn("status", ["active"])
                ->update([
                    "motive" => $motive,
                    "status" => "canceled",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                    "canceled_at" => now(),
                    "canceled_by" => $userId,
                ]);

        });

        $sale = $saleHeader->fresh();
        $sale->setAttribute("restore_stock_policy_enabled", $restoreStockPolicyEnabled);
        $sale->setAttribute("stock_restored_on_cancellation", $stockRestored);

        return $sale;

    }

    /**
     * Find sale header by ID
     *
     * @param  int  $id Sale header ID
     */
    public static function findById(int $companyId, int $id): ?SaleHeader {

        return SaleHeader::query()
            ->find($id);

    }

    /**
     * Get paginated list of sales
     *
     * @param  int  $companyId Company ID
     * @param  array  $filters Filter parameters
     * @param  int  $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getPaginatedList(
        int $companyId,
        array $filters = [],
        int $perPage = 15,
        ?int $userId = null
    ) {

        $branchQuery = \App\Models\System\Organizations\Branch::query()
            ->with(["series"]);

        $branchIds = $userId === null
            ? null
            : \App\Services\System\Base\CompanyReferenceDataService::forUser($userId)->allowedBranchIds();

        if($branchIds !== null) {

            $branchQuery->whereIn("id", $branchIds);

        }

        $branches = $branchQuery->get();

        $serieIds = $branches->pluck("series.*.id")->flatten();

        $query = SaleHeader::whereIn("serie_id", $serieIds)
            ->with(["serie.documentType", "serie.branch", "holder", "currency", "warehouse", "deliveryMethod", "taxes", "payments"]);

        // Apply filters
        self::applyFilters($query, $filters);

        // Apply ordering

        $query->orderBy("id", "DESC");

        return $query->paginate($perPage);

    }

    /**
     * Apply filters to query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private static function applyFilters($query, array $filters): void {

        if(Utilities::isDefined($filters["serie_id"])) {

            $query->where("serie_id", $filters["serie_id"]);

        }

        if(Utilities::isDefined($filters["sequential"])) {

            $query->where("sequential", $filters["sequential"]);

        }

        if(Utilities::isDefined($filters["issue_date"])) {

            $query->where("issue_date", $filters["issue_date"]);

        }

        if(Utilities::isDefined($filters["start_date"])) {

            $query->where("issue_date", ">=", Utilities::startOfDay($filters["start_date"]));

        }

        if(Utilities::isDefined($filters["end_date"])) {

            $query->where("issue_date", "<=", Utilities::endOfDay($filters["end_date"]));

        }

        if(Utilities::isDefined($filters["branch_id"])) {

            $query->whereHas("serie", fn($serie) => $serie->where("branch_id", $filters["branch_id"]));

        }

        if(Utilities::isDefined($filters["holder_id"])) {

            $query->where("holder_id", $filters["holder_id"]);

        }

        if(Utilities::isDefined($filters["status"])) {

            $query->where("status", $filters["status"]);

        }

    }
}
