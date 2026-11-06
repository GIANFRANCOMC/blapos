<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Warehouses\{StockAlertsExport};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use App\Services\System\Warehouses\StockManagement\{StockManagementService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB, Mail};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class StockAlertsCoverageTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();
        Mail::fake();

    }

    public function test_current_alerts_cover_empty_and_low_stock_with_or_without_saved_alerts(): void {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $empty = $this->product($warehouseId, "ALERT-EMPTY", 0, 0);
        $negative = $this->product($warehouseId, "ALERT-NEGATIVE", -2, 0);
        $emptyConfigured = $this->product($warehouseId, "ALERT-EMPTY-MIN", 0, 5);
        $low = $this->product($warehouseId, "ALERT-LOW", 2, 5);
        $atMinimum = $this->product($warehouseId, "ALERT-AT-MIN", 5, 5);
        $healthy = $this->product($warehouseId, "ALERT-HEALTHY", 8, 5);
        $this->product($warehouseId, "ALERT-NO-MIN-HEALTHY", 2, 0);
        $missing = $this->product($warehouseId, "ALERT-MISSING", null, 0);
        $inactive = $this->product($warehouseId, "ALERT-INACTIVE-STOCK", 20, 5);
        DB::table("warehouse_items")->where("warehouse_id", $warehouseId)->where("item_id", $inactive)->update(["status" => "inactive"]);
        $service = $this->product($warehouseId, "ALERT-SERVICE", null, 0);
        DB::table("items")->where("id", $service)->update(["type" => "service"]);

        foreach([$low, $low, $healthy] as $itemId) {

            DB::table("inventory_stock_alerts")->insert([
                "warehouse_item_id" => DB::table("warehouse_items")->where("warehouse_id", $warehouseId)->where("item_id", $itemId)->value("id"),
                "quantity" => 1,
                "minimum_stock" => 99,
                "status" => "open",
                "detected_at" => now()->subDay(),
            ]);

        }

        $otherWarehouse = DB::table("warehouses")->insertGetId([
            "branch_id" => DB::table("warehouses")->where("id", $warehouseId)->value("branch_id"),
            "name" => "Almacén de contraste",
            "status" => "active",
        ]);
        DB::table("warehouse_items")->insert([
            "warehouse_id" => $otherWarehouse,
            "item_id" => $empty,
            "quantity" => 50,
            "minimum_stock" => 5,
            "status" => "active",
        ]);

        $savedAlerts = DB::table("inventory_stock_alerts")->count();
        $savedStock = DB::table("warehouse_items")->count();
        $savedMovements = DB::table("inventory_movements")->count();
        $expected = [
            "" => [$empty, $negative, $emptyConfigured, $low, $atMinimum, $missing, $inactive],
            "configured" => [$emptyConfigured, $low, $atMinimum],
            "unconfigured" => [$empty, $negative, $missing, $inactive],
        ];

        foreach($expected as $minimumStatus => $ids) {

            $filters = ["warehouse_id" => $warehouseId, "product_search" => "ALERT-", "minimum_status" => $minimumStatus];
            $page = StockManagementService::getStockAlerts($filters, 2);
            $this->assertSame(count($ids), $page->total());
            $this->assertCount(2, $page->items());
            $rows = StockManagementService::getStockAlerts($filters, 100)->getCollection();
            $this->assertEqualsCanonicalizing($ids, $rows->pluck("warehouse_item.item.id")->all());
            $report = StockManagementService::stockAlertsReportQuery($warehouseId, "ALERT-", $minimumStatus);
            $this->assertEqualsCanonicalizing($ids, $report->pluck("products.id")->all());
            $this->assertEqualsCanonicalizing($ids, (new StockAlertsExport($warehouseId, "ALERT-", $minimumStatus))->query()->pluck("products.id")->all());

        }

        $rows = StockManagementService::getStockAlerts(["warehouse_id" => $warehouseId, "product_search" => "ALERT-"], 100)->getCollection();
        $this->assertSame(2.0, $rows->firstWhere("id", $low)["quantity"]);
        $this->assertSame(5.0, $rows->firstWhere("id", $low)["minimum_stock"]);
        $this->assertStringEndsWith("Z", $rows->firstWhere("id", $low)["detected_at"]);
        $this->assertNull($rows->firstWhere("id", $missing)["detected_at"]);
        $this->assertSame(0.0, $rows->firstWhere("id", $inactive)["quantity"]);
        $this->assertSame(0, StockManagementService::stockAlertsReportQuery($otherWarehouse, "ALERT-EMPTY")->where("products.id", $empty)->count());

        $generalEmpty = StockManagementService::getPaginatedList($warehouseId, 100, "ALERT-", "empty");
        $this->assertEqualsCanonicalizing([$empty, $negative, $emptyConfigured, $missing, $inactive], $generalEmpty->getCollection()->pluck("id")->all());
        $this->assertSame(0.0, (float) $generalEmpty->getCollection()->firstWhere("id", $inactive)->stock_quantity);
        $this->assertSame($savedAlerts, DB::table("inventory_stock_alerts")->count());
        $this->assertSame($savedStock, DB::table("warehouse_items")->count());
        $this->assertSame($savedMovements, DB::table("inventory_movements")->count());
        Mail::assertNothingSent();

        DB::table("warehouse_items")->where("warehouse_id", $warehouseId)->where("item_id", $low)->update(["minimum_stock" => 0]);
        $this->assertSame(0, StockManagementService::stockAlertsReportQuery($warehouseId, "ALERT-LOW")->count());

    }

    public function test_zero_stock_without_a_minimum_opens_and_resolves_alerts_on_movements(): void {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $itemId = $this->product($warehouseId, "ALERT-MOVEMENT", 1, 0);
        $stockId = DB::table("warehouse_items")->where("warehouse_id", $warehouseId)->where("item_id", $itemId)->value("id");
        $userId = (int) DB::table("users")->value("id");
        $movement = [
            "warehouse_id" => $warehouseId,
            "item_id" => $itemId,
            "user_id" => $userId,
            "origin_type" => "manual",
            "reason" => "Verificar alerta sin mínimo configurado.",
        ];

        InventoryMovementService::apply($movement + ["movement_type" => "exit", "quantity" => 1]);
        $this->assertDatabaseHas("inventory_stock_alerts", ["warehouse_item_id" => $stockId, "quantity" => 0, "minimum_stock" => 0, "status" => "open"]);
        InventoryMovementService::apply($movement + ["movement_type" => "entry", "quantity" => 2]);
        $this->assertSame(0, DB::table("inventory_stock_alerts")->where("warehouse_item_id", $stockId)->where("status", "open")->count());
        $this->assertDatabaseHas("inventory_stock_alerts", ["warehouse_item_id" => $stockId, "status" => "resolved", "resolved_by" => $userId]);
        $this->assertSame(0, StockManagementService::getStockAlerts(["warehouse_id" => $warehouseId, "product_search" => "ALERT-MOVEMENT"], 10)->total());
        $this->assertSame(1, StockManagementService::getStockAlerts(["warehouse_id" => $warehouseId, "status" => "resolved", "product_search" => "ALERT-MOVEMENT"], 10)->total());

    }

    private function product(int $warehouseId, string $code, ?float $quantity, float $minimum): int {

        $itemId = DB::table("items")->insertGetId([
            "internal_code" => $code,
            "name" => $code,
            "price" => 1,
            "currency_id" => DB::table("currencies")->value("id"),
            "measurement_unit_id" => DB::table("measurement_units")->value("id"),
            "type" => "product",
            "status" => "active",
        ]);

        if($quantity !== null) {

            DB::table("warehouse_items")->insert([
                "warehouse_id" => $warehouseId,
                "item_id" => $itemId,
                "quantity" => $quantity,
                "minimum_stock" => $minimum,
                "average_cost" => 1,
                "inventory_value" => max(0, $quantity),
                "status" => "active",
            ]);

        }

        return $itemId;

    }

}
