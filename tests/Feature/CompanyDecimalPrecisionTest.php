<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Warehouses\{StockAlertsExport};
use App\Helpers\System\{Utilities};
use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureOperationalScope, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use App\Services\System\Finance\{CommercialDocumentSettlementService};
use App\Services\System\General\{MasterDataService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService, CompanySettingService};
use App\Services\System\Purchases\{PurchaseService};
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
            $this->assertTrue(Validator::make(["value" => $amount], ["value" => "decimal:0,{$precision}"])->passes());
            $this->assertFalse(Validator::make(["value" => "1.".str_repeat("1", $precision + 1)], ["value" => "decimal:0,{$precision}"])->passes());

        }

        app(CompanyProvisioningService::class)->enable();
        $this->assertSame("3", $this->setting()->value);
        $this->assertSame("1.005", (string) DB::table("items")->where("id", $itemId)->value("price"));
        $this->assertSame(-1.01, Utilities::round(-1.005, 2));
        $this->assertSame("MIL UNO CON 005/1000 SOLES", Utilities::convertNumberToWords("1001.005"));
        $this->savePrecision(0);
        $this->assertSame("MIL SOLES", Utilities::convertNumberToWords("999.5"));

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
        $this->assertSame(3, Utilities::decimalPrecision());

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
