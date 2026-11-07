<?php

declare(strict_types=1);

namespace Tests\Feature;

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

}
