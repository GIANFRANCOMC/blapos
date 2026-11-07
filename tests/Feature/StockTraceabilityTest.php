<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\System\Warehouses\{StockManagementController};
use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{DB};
use Illuminate\Validation\{ValidationException};
use Symfony\Component\HttpKernel\Exception\{HttpException};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class StockTraceabilityTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();
        $this->actingAs(User::query()->firstOrFail());

    }

    public function test_traceability_is_paginated_and_isolated_by_product_and_warehouse_without_costs(): void {

        [$warehouseId, $itemId] = $this->fixture();
        $before = DB::table("inventory_movements")->count();
        $records = app(StockManagementController::class)->traceability($this->request($warehouseId, $itemId));
        $this->assertSame(13, $records->total());
        $this->assertSame(10, $records->perPage());
        $this->assertCount(10, $records->items());
        $this->assertSame(2, $records->lastPage());
        $this->assertSame("TRACE-12", $records->items()[0]["reference"]);
        $this->assertSame(13.0, $records->items()[0]["quantity_after"]);
        foreach($records->items() as $movement) {

            $this->assertStringStartsWith("TRACE-", $movement["reference"]);
            foreach(["unit_cost", "value_before", "value_change", "value_after", "metadata", "item", "warehouse"] as $key) $this->assertArrayNotHasKey($key, $movement);
            $this->assertSame(["name"], array_keys($movement["user"]));

        }
        $this->assertSame($before, DB::table("inventory_movements")->count());

    }

    public function test_a_stock_reader_can_consult_without_edit_or_general_kardex_permissions(): void {

        [$warehouseId, $itemId] = $this->fixture();
        $this->readerRole();
        $this->assertFalse(RolePermissionService::canAccessRoute(auth()->user(), "stocks_management.levels.update", "POST"));
        $this->assertFalse(RolePermissionService::canAccessRoute(auth()->user(), "stocks_management.movements"));
        $this->assertTrue(RolePermissionService::canAccessRoute(auth()->user(), "stocks_management.traceability"));
        $this->assertSame(13, app(StockManagementController::class)->traceability($this->request($warehouseId, $itemId))->total());

    }

    public function test_traceability_rejects_a_warehouse_outside_the_reader_scope(): void {

        [$warehouseId, $itemId] = $this->fixture();
        $this->readerRole(true);
        try {

            app(StockManagementController::class)->traceability($this->request($warehouseId, $itemId));
            $this->fail("El almacén debe pertenecer al alcance del lector.");

        } catch(HttpException $exception) {

            $this->assertSame(404, $exception->getStatusCode());

        }

    }

    public function test_product_is_required_so_the_endpoint_cannot_return_the_general_kardex(): void {

        $this->expectException(ValidationException::class);
        app(StockManagementController::class)->traceability(Request::create("/stocks_management/traceability", "GET", ["warehouse_id" => DB::table("warehouses")->value("id")]));

    }

    private function request(int $warehouseId, int $itemId): Request {

        return Request::create("/stocks_management/traceability", "GET", ["warehouse_id" => $warehouseId, "item_id" => $itemId]);

    }

    private function readerRole(bool $restricted = false): void {

        $roleId = DB::table("roles")->insertGetId([
            "slug" => "trace-reader", "name" => "Lector de inventario", "is_full_access" => false,
            "branch_scope_mode" => "all", "cash_register_scope_mode" => "all", "warehouse_scope_mode" => $restricted ? "restricted" : "all", "status" => "active",
        ]);
        DB::table("role_sub_sections")->insert([
            "role_id" => $roleId, "sub_section_id" => DB::table("sub_sections")->where("dom_route", "stocks_management.stock.index")->value("id"),
            "actions" => json_encode(["view"]), "status" => "active",
        ]);
        auth()->user()->update(["role_id" => $roleId, "warehouse_scope_mode" => "inherit"]);
        $this->actingAs(auth()->user()->fresh());
        AccessScopeService::clearUserCache((int) auth()->id());

    }

    private function fixture(): array {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $otherWarehouseId = DB::table("warehouses")->insertGetId(["branch_id" => DB::table("warehouses")->where("id", $warehouseId)->value("branch_id"), "name" => "Otro almacén", "status" => "active"]);
        $product = ["price" => 1, "currency_id" => DB::table("currencies")->value("id"), "measurement_unit_id" => DB::table("measurement_units")->value("id"), "type" => "product", "status" => "active"];
        $itemId = DB::table("items")->insertGetId($product + ["internal_code" => "TRACE-P", "name" => "Perno"]);
        $otherItemId = DB::table("items")->insertGetId($product + ["internal_code" => "TRACE-T", "name" => "Tuerca"]);
        $movement = [
            "warehouse_id" => $warehouseId, "item_id" => $itemId, "user_id" => auth()->id(), "movement_type" => "entry", "origin_type" => "manual",
            "quantity_before" => 0, "quantity_change" => 1, "quantity_after" => 1, "unit_cost" => 7, "value_before" => 0, "value_change" => 7, "value_after" => 7,
            "reason" => "Movimiento de prueba", "created_at" => now(),
        ];
        for($index = 0; $index < 13; $index++) DB::table("inventory_movements")->insert(array_replace($movement, ["quantity_before" => $index, "quantity_after" => $index + 1, "metadata" => json_encode(["reference" => "TRACE-{$index}", "private_cost" => 7])]));
        DB::table("inventory_movements")->insert(array_replace($movement, ["item_id" => $otherItemId, "metadata" => json_encode(["reference" => "OTHER-PRODUCT"])]));
        DB::table("inventory_movements")->insert(array_replace($movement, ["warehouse_id" => $otherWarehouseId, "metadata" => json_encode(["reference" => "OTHER-WAREHOUSE"])]));

        return [$warehouseId, $itemId];

    }

}
