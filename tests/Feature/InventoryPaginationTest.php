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
        $this->assertSame(30, $movements->perPage());
        $this->assertCount(0, $movements->items());

        $summary = $controller->summary($request)->getData(true)["data"];
        $this->assertSame(30, $summary["per_page"]);
        $this->assertCount(30, $summary["data"]);
        $this->assertSame(35, $summary["total"]);

    }

}
