<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\System\Organizations\{User};
use App\Services\System\Operations\{ServiceOperationConfigService, ServiceOperationService};
use App\Services\System\Organizations\{BusinessProfileService};
use App\Services\System\Sales\{CommercialSelectionService, QuotationService, SaleConfigService, SaleService};
use App\Services\System\Warehouses\StockManagement\{StockManagementService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{Auth, DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class ServiceOperationPerformanceTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    private int $branchId;

    private int $userId;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

        $this->branchId = (int) DB::table("branches")
            ->value("id");

        $this->userId = (int) DB::table("users")
            ->where("email", "admin@example.test")
            ->value("id");

    }

    public function test_initial_configuration_does_not_embed_growing_catalogs(): void {

        $this->seedOperationOptions(40);
        ServiceOperationConfigService::clearCache();

        $params = ServiceOperationConfigService::getInitParams("restaurant", $this->userId);

        $this->assertSame([], $params->config->customers);
        $this->assertSame([], $params->config->items);
        $this->assertLessThan(20000, strlen(json_encode($params, JSON_THROW_ON_ERROR)));

    }

    public function test_remote_options_are_tenant_scoped_minimal_and_limited(): void {

        $this->seedOperationOptions(40);

        $customers = ServiceOperationService::options("customers", "Cliente");
        $items = ServiceOperationService::options("items", "Servicio", "service");

        $this->assertCount(30, $customers);
        $this->assertCount(30, $items);
        $this->assertSame(["id", "name", "document_number"], array_keys((array) $customers->first()));
        $this->assertSame(["id", "name", "type", "price"], array_keys((array) $items->first()));

    }

    public function test_sale_initial_config_stays_small_and_remote_options_reach_later_pages(): void {

        $this->seedOperationOptions(80);
        SaleConfigService::clearCache("main");

        $params = SaleConfigService::getInitParams("main", $this->userId);

        $this->assertSame([], $params->config->items->records);
        $this->assertCount(1, $params->config->customers->records);
        $this->assertLessThan(45000, strlen(json_encode($params, JSON_THROW_ON_ERROR)));

        $posParams = SaleConfigService::getInitParams("pos", $this->userId);

        $this->assertCount(80, $posParams->config->items->records);
        $this->assertCount(81, $posParams->config->customers->records);

        $first = CommercialSelectionService::search("items", "Servicio", 1);
        $second = CommercialSelectionService::search("items", "Servicio", 2);
        $customerPage = CommercialSelectionService::search("customers", "Cliente", 2);

        $this->assertCount(25, $first["records"]);
        $this->assertCount(25, $second["records"]);
        $this->assertCount(25, $customerPage["records"]);
        $this->assertTrue($first["has_more"]);
        $this->assertNotSame($first["records"][0]->id, $second["records"][0]->id);
        $this->assertSame("Servicio 80", CommercialSelectionService::search("items", "SERVICE-80")["records"][0]->name);

    }

    public function test_sale_list_and_delivery_config_do_not_embed_the_customer_catalog(): void {

        $this->seedOperationOptions(80);
        SaleConfigService::clearCache("list");
        SaleConfigService::clearCache("deliveries");

        foreach(["list", "deliveries"] as $page) {

            $config = SaleConfigService::getInitParams($page, $this->userId)->config;

            $this->assertFalse(property_exists($config, "customers"));
            $this->assertLessThan(30000, strlen(json_encode($config, JSON_THROW_ON_ERROR)));

        }

    }

    public function test_remote_sales_options_require_the_corresponding_creation_module(): void {

        $this->seedOperationOptions(40);
        $user = User::query()->findOrFail($this->userId);
        Auth::login($user);

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ]);

        $this->getJson(route("sales.options", ["resource" => "items", "search" => "SERVICE-40"]))
            ->assertOk()
            ->assertJsonPath("data.records.0.name", "Servicio 40");

        $this->getJson(route("quotations.options", ["resource" => "customers", "search" => "Cliente 40"]))
            ->assertOk()
            ->assertJsonPath("data.records.0.name", "Cliente 40");

        $salesIndexId = (int) DB::table("sub_sections")
            ->where("dom_route", "sales.index")
            ->value("id");

        BusinessProfileService::updateModules([$salesIndexId], $this->userId);

        DB::table("customers")
            ->where("document_number", "00000040")
            ->update(["status" => "inactive"]);

        $customerOptions = $this->getJson(route("sales.customerOptions", ["search" => "Cliente 40"]))
            ->assertOk()
            ->assertJsonPath("data.records.0.name", "Cliente 40");

        $this->assertSame(
            ["id", "name", "document_number"],
            array_keys($customerOptions->json("data.records.0"))
        );

        $this->getJson(route("sales.options", ["resource" => "items"]))
            ->assertForbidden();

        $this->getJson(route("quotations.options", ["resource" => "customers"]))
            ->assertForbidden();

        $deliveriesId = (int) DB::table("sub_sections")
            ->where("dom_route", "sales.deliveries.index")
            ->value("id");

        BusinessProfileService::updateModules([$deliveriesId], $this->userId);

        $this->getJson(route("sales.customerOptions", ["search" => "Cliente 40"]))
            ->assertOk()
            ->assertJsonPath("data.records.0.name", "Cliente 40");

        $this->getJson(route("sales.options", ["resource" => "items"]))
            ->assertForbidden();

    }

    public function test_quotation_draft_hydrates_selected_records_outside_the_first_page(): void {

        $this->seedOperationOptions(80);

        $customerId = (int) DB::table("customers")
            ->where("document_number", "00000080")
            ->value("id");

        $itemId = (int) DB::table("items")
            ->where("internal_code", "SERVICE-80")
            ->value("id");

        $currencyId = (int) DB::table("currencies")->value("id");
        $quotationId = (int) DB::table("quotation_headers")->insertGetId([
            "branch_id" => $this->branchId,
            "holder_id" => $customerId,
            "seller_id" => $this->userId,
            "currency_id" => $currencyId,
            "reference" => "COT-REMOTE-80",
            "issue_date" => now()->toDateString(),
            "total" => 10,
            "status" => "draft",
        ]);

        DB::table("quotation_items")->insert([
            "quotation_header_id" => $quotationId,
            "item_id" => $itemId,
            "currency_id" => $currencyId,
            "name" => "Servicio 80",
            "type" => "service",
            "quantity" => 1,
            "price" => 10,
            "total" => 10,
            "status" => "active",
        ]);

        $draft = QuotationService::saleDraft($quotationId);

        $this->assertSame($customerId, (int) $draft["customer"]->id);
        $this->assertSame("Cliente 80", $draft["customer"]->name);
        $this->assertSame($itemId, (int) $draft["details"][0]["item"]->id);
        $this->assertSame("Servicio 80", $draft["details"][0]["item"]->name);
        $this->assertNotNull($draft["details"][0]["item"]->currency);

    }

    public function test_sale_and_inventory_lists_keep_fixed_query_budgets_at_volume(): void {

        $this->seedOperationOptions(1000);

        $serieId = (int) DB::table("series")->value("id");
        $customerId = (int) DB::table("customers")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");
        $warehouseId = (int) DB::table("warehouses")->value("id");
        $sales = [];
        $products = [];
        $timestamp = now();

        foreach(range(1, 1000) as $number) {

            $sales[] = [
                "serie_id" => $serieId,
                "sequential" => $number,
                "holder_id" => $customerId,
                "seller_id" => $this->userId,
                "currency_id" => $currencyId,
                "issue_date" => $timestamp->toDateString(),
                "total" => 10,
                "status" => "active",
            ];

            $products[] = [
                "internal_code" => "BENCH-PRODUCT-{$number}",
                "name" => "Producto de carga {$number}",
                "price" => 10,
                "currency_id" => $currencyId,
                "type" => "product",
                "status" => "active",
            ];

        }

        foreach(array_chunk($sales, 250) as $chunk) {

            DB::table("sales_header")->insert($chunk);

        }

        foreach(array_chunk($products, 250) as $chunk) {

            DB::table("items")->insert($chunk);

        }

        $productIds = DB::table("items")
            ->where("internal_code", "like", "BENCH-PRODUCT-%")
            ->pluck("id");

        foreach($productIds->chunk(250) as $ids) {

            DB::table("warehouse_items")->insert($ids->map(fn($id) => [
                "warehouse_id" => $warehouseId,
                "item_id" => $id,
                "quantity" => 10,
                "minimum_stock" => 2,
                "status" => "active",
            ])->all());

        }

        SaleConfigService::clearCache("main");
        DB::flushQueryLog();
        DB::enableQueryLog();
        $started = microtime(true);
        $saleConfig = SaleConfigService::getInitParams("main", $this->userId);
        $configBytes = strlen(json_encode($saleConfig, JSON_THROW_ON_ERROR));
        $configMs = round((microtime(true) - $started) * 1000, 1);
        $configQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $started = microtime(true);
        $itemOptions = CommercialSelectionService::search("items", "BENCH-PRODUCT", 1);
        $optionBytes = strlen(json_encode($itemOptions, JSON_THROW_ON_ERROR));
        $optionMs = round((microtime(true) - $started) * 1000, 1);
        $optionQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $started = microtime(true);
        $salePage = SaleService::getPaginatedList([], 25, $this->userId);
        $saleBytes = strlen(json_encode($salePage, JSON_THROW_ON_ERROR));
        $saleMs = round((microtime(true) - $started) * 1000, 1);
        $saleQueries = count(DB::getQueryLog());

        DB::flushQueryLog();
        $started = microtime(true);
        $stockPage = StockManagementService::getPaginatedList($warehouseId, 25);
        $stockBytes = strlen(json_encode($stockPage, JSON_THROW_ON_ERROR));
        $stockMs = round((microtime(true) - $started) * 1000, 1);
        $stockQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        fwrite(STDERR, PHP_EOL."READ_BENCHMARK ".json_encode([
            "new_sale_config" => ["customers" => count($saleConfig->config->customers->records), "items" => count($saleConfig->config->items->records), "queries" => $configQueries, "ms" => $configMs, "bytes" => $configBytes],
            "item_options" => ["page" => count($itemOptions["records"]), "queries" => $optionQueries, "ms" => $optionMs, "bytes" => $optionBytes],
            "sales" => ["rows" => $salePage->total(), "page" => $salePage->count(), "queries" => $saleQueries, "ms" => $saleMs, "bytes" => $saleBytes],
            "stock" => ["rows" => $stockPage->total(), "page" => $stockPage->count(), "queries" => $stockQueries, "ms" => $stockMs, "bytes" => $stockBytes],
        ], JSON_THROW_ON_ERROR).PHP_EOL);

        $this->assertSame([], $saleConfig->config->items->records);
        $this->assertCount(1, $saleConfig->config->customers->records);
        $this->assertLessThan(45000, $configBytes);
        $this->assertCount(25, $itemOptions["records"]);
        $this->assertLessThanOrEqual(8, $optionQueries);
        $this->assertSame(1000, $salePage->total());
        $this->assertSame(25, $salePage->count());
        $this->assertLessThanOrEqual(20, $saleQueries);
        $this->assertSame(1000, $stockPage->total());
        $this->assertSame(25, $stockPage->count());
        $this->assertLessThanOrEqual(5, $stockQueries);

    }

    public function test_restaurant_board_returns_floors_and_stations_in_one_composition(): void {

        $floorId = DB::table("service_floors")->insertGetId([
            "branch_id" => $this->branchId,
            "code" => "MAIN",
            "name" => "Salón principal",
            "level_number" => 1,
            "sort_order" => 1,
            "status" => "active",
        ]);
        DB::table("service_stations")->insert([
            "branch_id" => $this->branchId,
            "service_floor_id" => $floorId,
            "code" => "M01",
            "name" => "Mesa 1",
            "station_type" => "table",
            "capacity" => 4,
            "position_x" => 0,
            "position_y" => 0,
            "color" => "#2563EB",
            "shape" => "round",
            "status" => "active",
        ]);

        $board = ServiceOperationService::board($this->userId, $this->branchId);

        $this->assertSame($floorId, $board["selected_floor_id"]);
        $this->assertCount(1, $board["floors"]);
        $this->assertCount(1, $board["stations"]);
        $this->assertSame("Mesa 1", $board["stations"]->first()->name);

    }

    private function seedOperationOptions(int $count): void {

        $identityDocumentTypeId = (int) DB::table("identity_document_types")
            ->value("id");

        $currencyId = (int) DB::table("currencies")
            ->value("id");

        $now = now();
        $customers = [];
        $items = [];

        foreach(range(1, $count) as $number) {

            $customers[] = [
                "identity_document_type_id" => $identityDocumentTypeId,
                "document_number" => str_pad((string) $number, 8, "0", STR_PAD_LEFT),
                "name" => "Cliente {$number}",
                "status" => "active",
                "created_at" => $now,
            ];

            $items[] = [
                "internal_code" => "SERVICE-{$number}",
                "name" => "Servicio {$number}",
                "price" => 10,
                "currency_id" => $currencyId,
                "type" => "service",
                "capacity_control_enabled" => false,
                "capacity_used" => 0,
                "status" => "active",
                "created_at" => $now,
            ];

        }

        DB::table("customers")->insert($customers);
        DB::table("items")->insert($items);

    }
}
