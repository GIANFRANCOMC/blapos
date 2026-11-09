<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Warehouses\InventoryReportExport;
use App\Http\Controllers\System\Catalogs\{BrandController, CategoryController, ProductController, RecipeController, ServiceController, SubscriptionController};
use App\Http\Controllers\System\Warehouses\StockManagementController;
use App\Models\System\Organizations\User;
use Database\Seeders\CommercialCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ProvisionsSystemDatabase;
use Tests\TestCase;

final class InventoryPaginationTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    public function test_inventory_lists_never_exceed_thirty_records_per_page(): void {

        $this->provisionSystemDatabase();
        app(CommercialCatalogSeeder::class)->run(35, 0, 0);
        $this->actingAs(User::query()->firstOrFail());

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $controller = app(StockManagementController::class);
        $request = Request::create("/inventory/list", "GET", [
            "warehouse_id" => $warehouseId,
            "per_page" => 500,
        ]);

        $stock = $controller->list($request);
        $this->assertSame(30, $stock->perPage());
        $this->assertCount(30, $stock->items());
        $this->assertSame(35, $stock->total());

        $movements = $controller->movements($request);
        $movementCount = DB::table("inventory_movements")->where("warehouse_id", $warehouseId)->count();
        $this->assertSame(30, $movements->perPage());
        $this->assertCount(min(30, $movementCount), $movements->items());
        $this->assertSame($movementCount, $movements->total());

        $summary = $controller->summary($request)->getData(true)["data"];
        $this->assertSame(30, $summary["per_page"]);
        $this->assertCount(30, $summary["data"]);
        $this->assertSame(35, $summary["total"]);

        $defaultRequest = Request::create("/inventory/list", "GET", [
            "warehouse_id" => $warehouseId,
        ]);
        $stockDefault = $controller->list($defaultRequest);
        $this->assertSame(10, $stockDefault->perPage());
        $this->assertCount(10, $stockDefault->items());
        $movementsDefault = $controller->movements($defaultRequest);
        $this->assertSame(10, $movementsDefault->perPage());
        $this->assertCount(min(10, $movementCount), $movementsDefault->items());
        $this->assertSame(10, $controller->guides($defaultRequest)->perPage());
        $this->assertSame(30, $controller->guides($request)->perPage());

        $summaryDefault = $controller->summary($defaultRequest)->getData(true)["data"];
        $this->assertSame(10, $summaryDefault["per_page"]);
        $this->assertCount(10, $summaryDefault["data"]);

        DB::table("warehouse_items")->where("warehouse_id", $warehouseId)->update(["quantity" => 0]);
        $alertsDefault = $controller->alerts(Request::create("/inventory/alerts", "GET", [
            "warehouse_id" => $warehouseId,
            "status" => "open",
        ]));
        $this->assertSame(10, $alertsDefault->perPage());
        $this->assertCount(10, $alertsDefault->items());
        $this->assertSame(35, $alertsDefault->total());

    }

    public function test_commercial_catalog_uses_ten_records_by_default_and_respects_an_explicit_size(): void {

        $this->provisionSystemDatabase();
        $this->actingAs(User::query()->firstOrFail());

        foreach([
            BrandController::class, CategoryController::class, ProductController::class,
            RecipeController::class, ServiceController::class, SubscriptionController::class,
        ] as $controllerClass) {
            $controller = app($controllerClass);
            $this->assertSame(10, $controller->list(Request::create("/catalog/list"))->perPage(), $controllerClass);
            $this->assertSame(20, $controller->list(Request::create("/catalog/list", "GET", ["per_page" => 20]))->perPage(), $controllerClass);
        }

        $recipes = app(RecipeController::class);
        $this->assertSame(10, $recipes->wasteRecords(Request::create("/recipes/waste"))->getData(true)["data"]["per_page"]);
        $this->assertSame(20, $recipes->wasteRecords(Request::create("/recipes/waste", "GET", ["per_page" => 20]))->getData(true)["data"]["per_page"]);

    }

    public function test_kardex_combines_stock_and_movement_filters_before_pagination_and_export(): void {

        $this->provisionSystemDatabase();
        app(CommercialCatalogSeeder::class)->run(1, 0, 0);
        $user = User::query()->firstOrFail();
        $this->actingAs($user);
        $historicalAuthor = $user->replicate();
        $historicalAuthor->fill([
            "document_number" => "70001002",
            "email" => "kardex-author@example.test",
            "name" => "Responsable histórico",
            "status" => "inactive",
        ])->save();

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $item = DB::table("items")->where("type", "product")->first();
        $stock = DB::table("warehouse_items")->where("warehouse_id", $warehouseId)->where("item_id", $item->id);
        $stock->update(["quantity" => 3, "minimum_stock" => 5, "status" => "active"]);

        $history = DB::table("inventory_movements")->where("warehouse_id", $warehouseId)->where("item_id", $item->id);
        $history->delete();
        $entry = [
            "warehouse_id" => $warehouseId,
            "item_id" => $item->id,
            "user_id" => auth()->id(),
            "movement_type" => "entry",
            "origin_type" => "manual",
            "quantity_before" => 0,
            "quantity_change" => 1,
            "quantity_after" => 1,
            "reason" => "Movimiento de prueba",
        ];
        $olderId = DB::table("inventory_movements")->insertGetId($entry + ["created_at" => now()->subDays(50)]);
        $newerId = DB::table("inventory_movements")->insertGetId(array_replace($entry, [
            "user_id" => $historicalAuthor->id,
            "created_at" => now()->subDays(45),
        ]));
        DB::table("inventory_movements")->insert([
            $entry + ["created_at" => now()->subDays(70)],
            array_replace($entry, ["movement_type" => "exit", "created_at" => now()->subDays(40)]),
        ]);

        $filters = [
            "warehouse_id" => $warehouseId,
            "per_page" => 1,
            "product_search" => $item->internal_code,
            "situation" => "low",
            "category_id" => DB::table("category_items")->where("item_id", $item->id)->value("category_id"),
            "brand_id" => $item->brand_id,
            "minimum_status" => "configured",
            "inactive_days" => 30,
            "movement_type" => "entry",
            "date_from" => now()->subDays(60)->toDateString(),
            "date_to" => now()->subDays(35)->toDateString(),
        ];

        $controller = app(StockManagementController::class);
        $list = fn(array $filters) => $controller->movements(Request::create("/inventory/movements", "GET", $filters));
        $page = $list($filters);
        $this->assertSame(2, $page->total());
        $this->assertSame([$newerId], $page->getCollection()->pluck("id")->all());

        $authorFilters = $filters + ["user_id" => $historicalAuthor->id];
        $authorPage = $list($authorFilters);
        $this->assertSame(1, $authorPage->total());
        $this->assertSame([$newerId], $authorPage->getCollection()->pluck("id")->all());

        foreach(["kardex", "valued"] as $view) {
            $report = new InventoryReportExport($view, $filters);
            $this->assertSame([$newerId, $olderId], $report->collection()->pluck("id")->all());
            $authorReport = new InventoryReportExport($view, $authorFilters);
            $this->assertSame([$newerId], $authorReport->collection()->pluck("id")->all());

            $params = $controller->initParams(Request::create("/inventory/initParams", "GET", ["page" => $view]));
            $this->assertSame("Responsable histórico", $params->config->movement_users->pluck("name", "id")->get($historicalAuthor->id));
        }

        $this->assertSame(0, $list(array_replace($filters, ["situation" => "healthy"]))->total());
        $this->assertSame(0, $list(array_replace($filters, ["minimum_status" => "unconfigured"]))->total());
        $this->assertSame(0, $list(array_replace($filters, ["inactive_days" => 60]))->total());

    }

}
