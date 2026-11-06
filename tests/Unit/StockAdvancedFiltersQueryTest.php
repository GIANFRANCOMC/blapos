<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\System\Catalogs\Item;
use App\Services\System\Warehouses\StockManagement\StockManagementService;
use ReflectionMethod;
use Tests\TestCase;

final class StockAdvancedFiltersQueryTest extends TestCase {

    public function test_filters_are_applied_before_fetching_records(): void {

        $method = new ReflectionMethod(StockManagementService::class, "applyStockFilters");
        $query = $method->invoke(null, Item::query()->orderBy("name"), [
            "category_id" => 5,
            "brand_id" => 7,
            "minimum_status" => "unconfigured",
            "inactive_days" => 30,
        ], 3);

        $sql = $query->toSql();
        $this->assertStringContainsString("category_items", $sql);
        $this->assertStringContainsString("brand_id", $sql);
        $this->assertStringContainsString("wi.warehouse_id = 3", $sql);
        $this->assertStringContainsString("wi.status = 'active'", $sql);
        $this->assertStringContainsString("inventory_movements", $sql);
        $this->assertStringNotContainsString("CASE WHEN", $sql);
        $this->assertContains(5, $query->getBindings());
        $this->assertContains(7, $query->getBindings());

    }

    public function test_consolidated_filters_use_only_authorized_warehouses(): void {

        $method = new ReflectionMethod(StockManagementService::class, "applyStockFilters");
        $query = $method->invoke(null, Item::query(), [
            "minimum_status" => "unconfigured",
            "inactive_days" => 60,
        ], null, [2, 4]);

        $sql = $query->toSql();
        $this->assertStringContainsString("wi.warehouse_id IN (2,4)", $sql);
        $this->assertStringContainsString("inventory_movements", $sql);
        $this->assertStringContainsString("SUM(wi.minimum_stock)", $sql);
        $this->assertContains(2, $query->getBindings());
        $this->assertContains(4, $query->getBindings());

    }

}
