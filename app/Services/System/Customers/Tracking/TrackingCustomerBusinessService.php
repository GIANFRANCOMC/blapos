<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Helpers\System\{Utilities};
use App\Models\System\Customers\{Attendance, Customer, Subscription};
use App\Models\System\Sales\{SaleHeader};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Carbon\{Carbon};

/**
 * Business Service for Customer Tracking Operations
 * Handles complex business logic for tracking customer information
 */
class TrackingCustomerBusinessService {
    public function __construct(private readonly TenantCompanyContext $companyContext) {
    }

    /**
     * Get valid customer by code or document number
     *
     * @param  string|int  $code Customer ID or document number
     * @param  int  $companyId Company ID
     * @param  string  $type Search type: "document_number" or empty
     */
    public function getValidCustomer($code, int $companyId, string $type = ""): ?Customer {

        $query = Customer::query()
            ->with(["identityDocumentType"]);

        if($type === "document_number") {

            return $query->where("document_number", $code)->first();

        }

        return $query->where("id", $code)->first();

    }

    /**
     * Get date range from code
     *
     * @param  string  $code Range code (e.g., "this_year", "last_3_months")
     * @return array Array with "from" and "to" Carbon dates
     */
    public function getDateRangeFromCode(string $code, ?string $from = null, ?string $to = null): array {

        $now = Carbon::now();

        if($code === "custom" && $from && $to) {

            $start = Carbon::parse($from)->startOfDay();
            $end = Carbon::parse($to)->endOfDay();

            if($start->greaterThan($end)) {

                throw new \InvalidArgumentException("La fecha inicial no puede ser posterior a la fecha final.");

            }

            return ["from" => $start, "to" => $end];

        }

        if($code === "this_year") {

            return [
                "from" => $now->copy()->startOfYear()->startOfDay(),
                "to" => $now->copy()->endOfDay(),
            ];

        }

        if(preg_match("/^last_(\d+)_([a-z]+)$/", $code, $matches)) {

            $amount = (int) $matches[1];
            $unit = $matches[2]; // "days", "months", "years"

            return [
                "from" => $now->copy()->sub($unit, $amount)->startOfDay(),
                "to" => $now->copy()->endOfDay(),
            ];

        }

        // Default: last 30 days
        return [
            "from" => $now->copy()->subDays(30)->startOfDay(),
            "to" => $now->copy()->endOfDay(),
        ];

    }

    /**
     * Get information by type
     *
     * @param  Customer  $customer Customer model
     * @param  array  $range Date range with "from" and "to"
     * @param  string  $type Information type: "sales", "subscriptions", "attendances"
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInformation(Customer $customer, array $range, string $type, ?array $allowedBranchIds = null) {

        switch($type) {

            case "sales":
                return SaleHeader::where("holder_id", $customer->id)
                    ->whereHas("serie.branch", function($query) use ($allowedBranchIds) {

                        if($allowedBranchIds !== null) {

                            $query->whereIn("id", $allowedBranchIds);

                        }

                    })
                    ->whereBetween("created_at", [$range["from"], $range["to"]])
                    ->with(["serie.documentType", "serie.branch", "currency"])
                    ->get();

            case "subscriptions":
                return Subscription::query()
                    ->where("customer_id", $customer->id)
                    ->when($allowedBranchIds !== null, fn($query) => $query->whereIn("branch_id", $allowedBranchIds))
                    ->whereBetween("created_at", [$range["from"], $range["to"]])
                    ->with(["branch"])
                    ->get();

            case "attendances":
                return Attendance::query()
                    ->where("customer_id", $customer->id)
                    ->when($allowedBranchIds !== null, fn($query) => $query->whereIn("branch_id", $allowedBranchIds))
                    ->whereBetween("created_at", [$range["from"], $range["to"]])
                    ->with(["branch"])
                    ->get();

            default:
                return collect();

        }

    }

    /**
     * Get complete tracking information for customer
     *
     * @param  array  $data Request data
     * @return array Response array with tracking information
     */
    public function get(array $data): array {

        $response = [
            "bool" => false,
            "msg" => "",
        ];

        $companyId = $this->companyContext->id();
        $customerId = $data["customer_id"] ?? "";
        $customerDocumentNumber = $data["customer_document_number"] ?? "";
        $periodType = $data["period_type"] ?? "last_3_months";
        $options = $data["options"] ?? [];
        $allowedBranchIds = $data["allowed_branch_ids"] ?? null;

        // Get customer
        $customer = $this->getValidCustomer(
            $customerDocumentNumber ?: $customerId,
            $companyId,
            $customerDocumentNumber ? "document_number" : ""
        );

        if(!Utilities::isDefined($customer)) {

            $response["msg"] = "No se ha encontrado el cliente solicitado.";

            return $response;

        }

        $range = $this->getDateRangeFromCode(
            $periodType,
            $data["start_date"] ?? null,
            $data["end_date"] ?? null
        );

        $response["tracking"] = [
            "customer" => $customer,
            "functions" => [],
            "extras" => [
                "period_type" => $periodType,
                "options" => $options,
            ],
        ];

        $information = $options["information"] ?? [];

        foreach($information as $opt) {

            $response["tracking"][$opt] = $this->getInformation($customer, $range, $opt, $allowedBranchIds);

            if(in_array($opt, ["sales", "subscriptions", "attendances"])) {

                $response["tracking"]["functions"]["subscription_end_dates"] = $customer->subscriptionEndDates();

            }

        }

        $sales = $response["tracking"]["sales"] ?? collect();

        $subscriptions = $response["tracking"]["subscriptions"] ?? collect();
        $attendances = $response["tracking"]["attendances"] ?? collect();
        $response["tracking"]["summary"] = [
            "sales_count" => $sales->count(),
            "active_sales_total" => (float) $sales->where("status", "active")->sum("total"),
            "canceled_sales_total" => (float) $sales->where("status", "canceled")->sum("total"),
            "active_subscriptions" => $subscriptions->where("status", "active")->count(),
            "attendances" => $attendances->whereIn("status", ["active", "finalized"])->count(),
        ];

        $response["bool"] = true;
        $response["msg"] = "Información encontrada";

        return $response;

    }
}
