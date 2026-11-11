<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Catalogs\Products\{ProductListExport};
use App\Exports\System\Warehouses\{StockAlertsExport};
use App\Helpers\System\{Numbers};
use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureOperationalScope, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use App\Models\System\Sales\{SaleHeader};
use App\Services\System\Catalogs\Products\{ProductService};
use App\Services\System\Customers\Tracking\{TrackingCustomerBusinessService};
use App\Services\System\Essentials\{DashboardService};
use App\Services\System\Finance\{AccountsPayableService, AccountsReceivableService, CommercialCreditAccountService, CommercialDocumentSettlementService, FinancialSettlementReportService};
use App\Services\System\General\{MasterDataService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService, CompanySettingService};
use App\Services\System\Purchases\{PurchaseService, SupplierService};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use App\Services\System\Warehouses\StockManagement\{StockManagementConfigService, StockManagementService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB, Mail, Validator};
use Illuminate\Validation\{ValidationException};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class CompanyDecimalPrecisionTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();
        Mail::fake();

    }

    public function test_saved_precision_drives_casts_bootstrap_and_exports_without_changing_history(): void {

        $itemId = $this->createItem();
        $userId = (int) DB::table("users")->value("id");
        $this->assertSame("2", $this->setting()->value);
        $this->assertSame(2, StockManagementConfigService::getInitParams("stock", $userId)->config->generalConfig->forms["inputs"]["round"]);

        foreach([0 => ["1", "#,##0"], 1 => ["1.0", "#,##0.0"], 2 => ["1.01", "#,##0.00"], 3 => ["1.005", "#,##0.000"]] as $precision => [$amount, $format]) {

            $this->savePrecision($precision);
            $this->assertSame((string) $precision, $this->setting()->value);
            $this->assertSame($precision, StockManagementConfigService::getInitParams("stock", $userId)->config->generalConfig->forms["inputs"]["round"]);
            $this->assertSame($amount, Item::findOrFail($itemId)->price);
            $this->assertSame($format, (new StockAlertsExport(1))->columnFormats()["D"]);
            $this->assertTrue(Validator::make(["value" => $amount], ["value" => Numbers::decimalRule()])->passes());
            $this->assertFalse(Validator::make(["value" => "1.".str_repeat("1", $precision + 1)], ["value" => Numbers::decimalRule()])->passes());
            $this->assertSame($amount, Numbers::spreadsheetDecimal(str_replace(".", ",", $amount), allowZero: false));
            $this->assertNull(Numbers::spreadsheetDecimal("1,".str_repeat("1", $precision + 1), allowZero: false));

        }

        app(CompanyProvisioningService::class)->enable();
        $this->assertSame("3", $this->setting()->value);
        $this->assertSame("1.005", (string) DB::table("items")->where("id", $itemId)->value("price"));
        $this->assertSame(-1.01, Numbers::round(-1.005, 2));
        $this->assertSame("MIL UNO CON 005/1000 SOLES", Numbers::convertNumberToWords("1001.005"));
        $this->savePrecision(0);
        $this->assertSame("MIL SOLES", Numbers::convertNumberToWords("999.5"));

    }

    public function test_existing_master_data_route_accepts_zero_and_returns_validation_errors(): void {

        $this->actingAs(User::query()->where("email", "admin@example.test")->firstOrFail());
        $this->withoutMiddleware([
            ResolveTenant::class, TrustHosts::class, EnsureTenantSession::class,
            EnsureAuthenticatedSession::class, EnsureOperationalScope::class,
        ]);
        $setting = $this->setting();
        $route = route("master_data.update", ["resource" => "company-settings", "id" => $setting->id]);
        $fields = ["group" => $setting->group, "key" => $setting->key, "value_type" => "integer", "status" => "active"];

        $this->patchJson($route, $fields + ["value" => 4])->assertUnprocessable()->assertJsonValidationErrors(["value"]);
        $this->patchJson($route, $fields + ["value" => 0])->assertOk();
        $this->assertSame("0", $this->setting()->value);

    }

    public function test_decimal_contract_matches_the_frontend_and_preserves_the_storage_limit(): void {

        $cases = json_decode(file_get_contents(base_path("tests/Fixtures/decimal-cases.json")), true, flags: JSON_THROW_ON_ERROR);

        foreach($cases["format"] as [$value, $precision, $expected]) {

            $this->assertSame($expected, Numbers::formatDecimal($value, $precision, ""));
            $this->assertSame((float) $expected, Numbers::round($value, $precision));

        }

        foreach($cases["arithmetic"] as [$operation, $precision, $arguments, $expected]) {

            $this->savePrecision($precision);
            $this->assertSame($expected, Numbers::formatDecimal(Numbers::$operation(...$arguments), thousandsSeparator: ""));

        }

        foreach($cases["documentTax"] as [$precision, $tax, $lines, $expected]) {

            $this->savePrecision($precision);
            $this->assertSame($expected, Numbers::documentTax($tax, $lines));

        }

        $rules = ["amount" => Numbers::numericRules(required: true)];
        $this->assertTrue(Validator::make(["amount" => "999999999999.999"], $rules)->passes());
        $this->assertFalse(Validator::make(["amount" => "1000000000000.000"], $rules)->passes());
        $this->assertSame("999999999999.999", Numbers::inputConfig()["maxValue"]);
        $this->assertSame("10999999999999.989", Numbers::sumText(array_fill(0, 11, "999999999999.999")));
        $this->assertSame("10999999999999.989", Numbers::spreadsheetNumber("10999999999999.989"));
        $this->assertSame(999999999999.999, Numbers::spreadsheetNumber("999999999999.999"));
        $this->assertSame("0.001", Numbers::subtractText("10999999999999.989", "10999999999999.988"));
        $this->assertSame(1.005, Numbers::average(["1.00", null, "1.01"]));
        $this->assertSame("999999999999.999", Numbers::spreadsheetDecimal(999999999999.999, allowZero: false));
        $this->assertNull(Numbers::spreadsheetDecimal(0.0004, allowZero: false));
        $nestedRules = ["items.*.amount" => $rules["amount"]];
        $validator = Numbers::prepareValidator(Validator::make(["items" => [
            ["amount" => 999999999999.999], ["amount" => 1e-3],
        ]], $nestedRules));
        $this->assertSame(["items" => [
            ["amount" => "999999999999.999"], ["amount" => "0.001"],
        ]], $validator->validate());
        $this->assertTrue(Numbers::prepareValidator(Validator::make(
            ["items" => [["amount" => 0.0004], ["amount" => "999999999999.99901"]]],
            $nestedRules
        ))->fails());

        $this->savePrecision(2);
        $this->assertSame(
            ["value" => "100.00", "amount" => 2.02],
            Numbers::commission("1.005", "2", "percentage", "101")
        );
        $itemId = $this->createItem();
        $item = Item::findOrFail($itemId);
        $item->status = "inactive";
        $item->save();
        $this->assertSame("1.005", DB::table("items")->where("id", $itemId)->value("price"));
        $this->assertSame("1.01", $item->fresh()->price);

        $item->price = "1.155";
        $item->save();
        $this->assertSame("1.160", DB::table("items")->where("id", $itemId)->value("price"));

    }

    public function test_settings_reject_invalid_values_and_bound_legacy_values(): void {

        foreach([4, -1, "1.5", null, true] as $invalid) {

            try {

                $this->savePrecision($invalid);
                $this->fail("El valor de precisión inválido llegó a guardarse.");

            }catch(ValidationException $exception) {

                $this->assertArrayHasKey("value", $exception->errors());
                $this->assertSame("2", $this->setting()->value);

            }

        }

        DB::table("company_settings")->where("id", $this->setting()->id)->update(["value" => "4"]);
        CompanySettingService::clearCache();
        $this->assertSame(3, Numbers::decimalPrecision());

    }

    public function test_stock_filters_follow_display_and_transfers_conserve_rounded_value(): void {

        $itemId = $this->createItem();
        $warehouses = DB::table("warehouses")->orderBy("id")->pluck("id");
        $sourceId = (int) $warehouses->first();
        $destinationId = (int) DB::table("warehouses")->insertGetId([
            "branch_id" => DB::table("warehouses")->where("id", $sourceId)->value("branch_id"),
            "name" => "Destino de precisión",
            "status" => "active",
        ]);
        $stockId = DB::table("warehouse_items")->insertGetId([
            "warehouse_id" => $sourceId, "item_id" => $itemId, "status" => "active",
            "quantity" => 0.004, "minimum_stock" => 0.004, "inventory_value" => 0,
        ]);
        $this->assertSame(1, StockManagementService::getPaginatedList($sourceId, 10, "PREC-1", "empty", ["minimum_status" => "unconfigured"])->total());
        $this->assertSame(1, StockManagementService::stockAlertsReportQuery($sourceId, "PREC-1", "unconfigured")->count());

        DB::table("warehouse_items")->insert([
            "warehouse_id" => $destinationId, "item_id" => $itemId, "status" => "active",
            "quantity" => "0.004", "minimum_stock" => "0.004", "inventory_value" => 0,
        ]);
        $product = ProductService::getPaginatedList(["filter_by" => "all", "word" => "PREC-1"])->items()[0];
        $this->assertSame("0.01", $product->total_stock_quantity);
        $this->assertSame(0.01, (new ProductListExport())->map($product)[10]);
        $this->assertSame("0.01", StockManagementService::getConsolidatedStock("PREC-1")->first()->stock_quantity);

        $this->savePrecision(3);
        $this->assertSame(0, StockManagementService::getPaginatedList($sourceId, 10, "PREC-1", "empty")->total());
        $this->assertSame(1, StockManagementService::stockAlertsReportQuery($sourceId, "PREC-1", "configured")->count());

        $this->savePrecision(0);
        DB::table("warehouse_items")->where("id", $stockId)->update(["quantity" => 3, "average_cost" => 2, "inventory_value" => 5]);
        $movement = InventoryMovementService::transfer([
            "source_warehouse_id" => $sourceId, "destination_warehouse_id" => $destinationId,
            "items" => [["item_id" => $itemId, "quantity" => 3]],
            "reason" => "Prueba de traslado valorizado.",
        ])["movements"][0];

        $this->assertSame("-5", $movement["exit"]->value_change);
        $this->assertSame("0", $movement["exit"]->value_after);
        $this->assertSame("5", $movement["entry"]->value_after);

    }

    public function test_one_configured_unit_short_is_not_an_exact_payment(): void {

        $this->savePrecision(1);
        $userId = (int) DB::table("users")->value("id");
        $payment = ["payment_method_id" => DB::table("payment_methods")->where("code", "cash")->value("id"), "amount" => 0.1];

        $this->expectExceptionMessage("El total de los métodos de pago debe coincidir con el total del documento.");
        CommercialDocumentSettlementService::payments("sale", 0.2, [$payment], $userId);

    }

    public function test_tax_on_a_rounded_line_matches_the_point_of_sale_preview(): void {

        $userId = (int) DB::table("users")->value("id");
        $details = [
            ["quantity" => "0.30", "price" => "0.35", "price_includes_tax" => true],
            ["quantity" => "0.30", "price" => "0.35", "price_includes_tax" => false],
        ];
        $igv = CommercialDocumentSettlementService::saleTaxes($details, $userId)->first();

        $this->assertSame(0.20, $igv["base_amount"]);
        $this->assertSame(0.04, $igv["amount"]);
        $this->assertSame(0.02, $igv["_total_impact"]);
        $this->assertSame(3, Numbers::floorQuotient("0.3", "0.1"));
        $this->assertSame("999999999999.99", Numbers::storage("999999999999.994"));

    }

    public function test_purchase_expenses_receipts_and_installments_preserve_their_totals_with_no_decimals(): void {

        $this->savePrecision(0);
        $warehouseId = (int) DB::table("warehouses")->value("id");
        $userId = (int) DB::table("users")->value("id");
        $supplierId = DB::table("suppliers")->insertGetId(["name" => "Proveedor de precisión", "status" => "active"]);
        $details = [];

        for($number = 1; $number <= 6; $number++) {

            $details[] = [
                "item_id" => $this->createItem("PREC-".$number),
                "quantity" => $number === 1 ? 3 : 1,
                "unit_cost" => 1,
            ];

        }

        $purchase = PurchaseService::create($userId, [
            "supplier_id" => $supplierId, "warehouse_id" => $warehouseId,
            "currency_id" => DB::table("currencies")->value("id"),
            "document_type" => "order", "issue_date" => now()->toDateString(),
            "due_date" => now()->addMonth()->toDateString(),
            "payment_modality" => "installments", "installment_count" => 24,
            "items" => $details, "expenses" => [
                ["expense_type" => "freight", "name" => "Flete", "amount" => 3, "allocation_method" => "equal"],
            ],
        ]);

        $installments = $purchase->accountPayable->installments;
        $this->assertCount(24, $installments);
        $this->assertEquals($purchase->total, $installments->sum("amount"));
        $this->assertGreaterThanOrEqual(0, (float) $installments->min("amount"));
        $this->assertEquals(3, $purchase->items->sum("allocated_expense_total"));
        $this->assertEquals(11, $purchase->receipts->flatMap->items->sum("total_cost"));
        $this->assertEquals(4, DB::table("warehouse_items")->where("item_id", $details[0]["item_id"])->value("inventory_value"));

        $overdue = $installments->first(fn($installment) => (int) $installment->pending_amount > 0);
        $overdue->update(["due_date" => now()->subDay()->toDateString()]);
        $summary = app(AccountsPayableService::class)->summary($userId, ["status" => "overdue"]);

        $this->assertSame(1, $summary["overdue_accounts"]);
        $this->assertSame($overdue->pending_amount, $summary["amounts"][0]["overdue"]);
        $this->assertSame($purchase->accountPayable->pending_amount, $summary["amounts"][0]["pending"]);
        $this->assertGreaterThan((int) $summary["amounts"][0]["overdue"], (int) $summary["amounts"][0]["pending"]);

    }

    public function test_financial_reports_and_customer_history_group_exact_amounts_by_currency(): void {

        $this->savePrecision(3);
        $date = now()->toDateString();
        $supplierId = DB::table("suppliers")->insertGetId(["name" => "Proveedor de dos monedas"]);
        $customerId = DB::table("customers")->insertGetId([
            "name" => "Cliente de dos monedas", "document_number" => "98765432",
            "identity_document_type_id" => DB::table("identity_document_types")->where("code", "dni")->value("id"),
        ]);
        $warehouseId = DB::table("warehouses")->value("id");
        $sellerId = DB::table("users")->value("id");
        $seriesId = DB::table("series")->value("id");
        DB::table("currencies")->insertOrIgnore([
            "code" => "USD", "sign" => "US$", "singular_name" => "Dólar", "plural_name" => "Dólares",
        ]);
        $currencyIds = DB::table("currencies")->whereIn("code", ["PEN", "USD"])->pluck("id", "code");
        $expected = ["PEN" => "123.456", "USD" => "789.012"];
        $sequence = 900110;

        foreach($expected as $code => $total) {

            $currencyId = $currencyIds[$code];
            $purchaseId = DB::table("purchase_headers")->insertGetId([
                "supplier_id" => $supplierId, "warehouse_id" => $warehouseId, "currency_id" => $currencyId,
                "issue_date" => $date, "reference" => "PREC-".$code, "total" => $total,
            ]);
            DB::table("purchase_payments")->insert([
                "purchase_header_id" => $purchaseId, "name" => "Efectivo", "amount" => $total,
            ]);
            DB::table("purchase_taxes")->insert([
                "purchase_header_id" => $purchaseId, "name" => "Tributo de prueba", "amount" => $total,
            ]);
            DB::table("sales_header")->insert([
                "serie_id" => $seriesId, "sequential" => $sequence++, "holder_id" => $customerId,
                "seller_id" => $sellerId, "currency_id" => $currencyId, "issue_date" => $date, "total" => $total,
            ]);

        }

        foreach(["taxes", "payments"] as $type) {

            $this->assertSame($expected, FinancialSettlementReportService::summarize($type, "purchase", $date, $date)
                ->where("name", $type === "taxes" ? "Tributo de prueba" : "Efectivo")
                ->pluck("amount", "code")->sortKeys()->all());

        }

        $supplier = SupplierService::paginate("Proveedor de dos monedas", 10)->items()[0];
        $this->assertSame($expected, $supplier->purchase_amounts->pluck("total", "code")->all());
        $this->assertSame($expected, DashboardService::getDashboardData($date)["sales"]["net"]["amounts"]
            ->pluck("total", "code")->all());
        $tracking = app(TrackingCustomerBusinessService::class)->get([
            "customer_id" => $customerId, "options" => ["information" => ["sales"]],
        ]);
        $this->assertSame($expected, $tracking["tracking"]["summary"]["active_sales_amounts"]->pluck("total", "code")->all());

        $sale = SaleHeader::query()->where("holder_id", $customerId)->where("currency_id", $currencyIds["PEN"])->firstOrFail();
        $sale->update(["payment_modality" => "installments", "balance_due" => $sale->total, "payment_status" => "unpaid"]);
        $account = CommercialCreditAccountService::createReceivable($sale, collect(), 2, now()->addMonth()->toDateString(), (int) $sellerId);
        $firstInstallment = $account->installments()->orderBy("due_date")->firstOrFail();
        $firstInstallment->update(["due_date" => now()->subDay()->toDateString()]);
        $summary = app(AccountsReceivableService::class)->summary((int) $sellerId, ["status" => "overdue"]);

        $this->assertSame(1, $summary["overdue_accounts"]);
        $this->assertSame($firstInstallment->pending_amount, $summary["amounts"][0]["overdue"]);
        $this->assertSame($account->pending_amount, $summary["amounts"][0]["pending"]);

    }

    private function setting(): object {

        return DB::table("company_settings")->where("group", "numeric_validation")->where("key", "decimal_precision")->first();

    }

    private function savePrecision(mixed $value): void {

        $setting = $this->setting();
        MasterDataService::save((int) DB::table("users")->value("id"), "company-settings", [
            "group" => $setting->group, "key" => $setting->key, "value" => $value,
            "value_type" => "integer", "status" => "active",
        ], $setting->id);

    }

    private function createItem(string $code = "PREC-1"): int {

        return DB::table("items")->insertGetId([
            "internal_code" => $code, "name" => "Producto de precisión", "price" => 1.005,
            "currency_id" => DB::table("currencies")->value("id"),
            "measurement_unit_id" => DB::table("measurement_units")->value("id"),
            "type" => "product", "status" => "active",
        ]);

    }

}
