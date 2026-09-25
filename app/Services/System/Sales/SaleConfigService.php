<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Customers\{Customer};
use App\Models\System\Finance\{CashSession};
use App\Models\System\Sales\{QuotationHeader, SaleHeader};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService, MasterReferenceDataService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use stdClass;

final class SaleConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "sale";

    }

    protected static function cachePages(): array {

        return ["main", "list", "deliveries"];

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::for($companyId, $userId);

        if($page === "list" || $page === "deliveries") {

            return self::data([
                "branches" => self::data([
                    "records" => $references->branchesWithSeries(),
                ]),
                "warehouses" => self::data([
                    "records" => $references->stockWarehouses(),
                ]),
                "customers" => self::data([
                    "records" => $references->customers(),
                ]),
                "salesHeader" => self::data([
                    "statuses" => SaleHeader::getStatuses(),
                ]),
                "saleDeliveries" => self::data([
                    "statuses" => \App\Models\System\Sales\SaleDelivery::getStatuses(),
                ]),
            ]);

        }

        $cashSessions = CashSession::query()
            ->with(["register", "branch"])
            ->where("status", "open");

        $cashRegisterIds = $references->allowedCashRegisterIds();

        if($cashRegisterIds !== null) {

            $cashSessions->whereIn("cash_register_id", $cashRegisterIds);

        }

        return self::data([
            "branches" => self::data([
                "records" => $references->branchesWithSeries(),
            ]),
            "warehouses" => self::data([
                "records" => $references->stockWarehouses(),
            ]),
            "currencies" => self::data([
                "records" => MasterReferenceDataService::currencies($companyId),
            ]),
            "customers" => self::data([
                "records" => $references->activeCustomers(),
                "identityDocumentTypes" => MasterReferenceDataService::customerIdentityDocuments($companyId),
                "genders" => Customer::getGenders(),
                "statuses" => Customer::getStatuses(),
            ]),
            "items" => self::data([
                "durationTypes" => Item::getDurationTypes(),
                "records" => $references->saleItems(),
            ]),
            "categories" => self::data([
                "records" => $references->categories(),
            ]),
            "taxes" => self::data([
                "records" => $references->taxesFor("sale"),
            ]),
            "paymentMethods" => self::data([
                "records" => $references->paymentMethodsFor("sale"),
            ]),
            "saleDeliveryMethods" => self::data([
                "records" => $references->saleDeliveryMethods(),
            ]),
            "users" => self::data([
                "records" => $references->users(),
                "current_id" => $userId,
            ]),
            "cashSessions" => self::data([
                "records" => $cashSessions->latest("opened_at")->get(),
            ]),
            "quotations" => self::data([
                "records" => QuotationHeader::query()
                    ->whereIn("status", ["draft", "sent", "accepted"])
                    ->with("holder:id,name,document_number")
                    ->latest("id")
                    ->limit(100)
                    ->get(["id", "reference", "holder_id", "issue_date", "valid_until", "total", "status"]),
            ]),
            "salesHeader" => self::data([
                "statuses" => SaleHeader::getStatuses(),
                "deliveryStatuses" => collect(SaleHeader::getDeliveryStatuses())
                    ->whereIn("code", ["pending", "delivered"])
                    ->values(),
                "paymentModalities" => SaleHeader::getPaymentModalities(),
                "defaultPaymentModality" => CompanySettingService::value(
                    $companyId,
                    CompanySettingService::SALES,
                    "default_payment_modality",
                    "paid_now"
                ),
                "installmentExtraPercentage" => (float) CompanySettingService::value(
                    $companyId,
                    CompanySettingService::SALES,
                    "installment_extra_percentage",
                    0
                ),
            ]),
        ]);

    }
}
