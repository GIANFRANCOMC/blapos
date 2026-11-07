<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\System\Warehouses\{StockManagementController};
use App\Http\Requests\System\Warehouses\{UpdateStockLevelsRequest};
use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{DB, Mail, Validator};
use Symfony\Component\HttpKernel\Exception\{HttpException};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class StockLevelsTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();
        $this->actingAs(User::query()->firstOrFail());
        Mail::fake();

    }

    public function test_editor_updates_levels_with_a_valued_correction_and_resolves_the_alert(): void {

        $data = $this->fixture();
        $response = app(StockManagementController::class)->updateStockLevels($this->request($data));
        $this->assertSame(200, $response->status());
        $this->assertTrue($response->getData(true)["bool"]);
        $this->assertDatabaseHas("warehouse_items", ["item_id" => $data["item_id"], "quantity" => 10, "minimum_stock" => 2, "average_cost" => 3, "inventory_value" => 30]);
        $this->assertDatabaseHas("inventory_movements", [
            "item_id" => $data["item_id"], "warehouse_id" => $data["warehouse_id"], "movement_type" => "correction",
            "origin_type" => "physical_count", "quantity_before" => 4, "quantity_after" => 10, "quantity_change" => 6,
            "user_id" => auth()->id(), "unit_cost" => 3, "value_before" => 12, "value_after" => 30,
        ]);
        $this->assertDatabaseHas("inventory_stock_alerts", ["warehouse_item_id" => $data["stock_id"], "status" => "resolved", "minimum_stock" => 2, "resolved_by" => auth()->id()]);
        $params = app(StockManagementController::class)->initParams(Request::create("/stocks_management/initParams", "GET", ["page" => "stock"]));
        $this->assertTrue($params->config->can_update_stock);

    }

    public function test_minimum_only_updates_do_not_create_movements_and_keep_alerts_in_sync(): void {

        $data = $this->fixture();
        InventoryMovementService::updateStockLevels(array_replace($data, ["quantity" => 4, "minimum_stock" => 1, "user_id" => auth()->id()]));
        $this->assertSame(0, DB::table("inventory_movements")->where("item_id", $data["item_id"])->count());
        $this->assertDatabaseHas("warehouse_items", ["item_id" => $data["item_id"], "quantity" => 4, "minimum_stock" => 1, "inventory_value" => 12, "updated_by" => auth()->id()]);
        $this->assertDatabaseHas("inventory_stock_alerts", ["warehouse_item_id" => $data["stock_id"], "status" => "resolved"]);

        $data = array_replace($data, ["quantity" => 4, "minimum_stock" => 8, "expected_minimum_stock" => 1]);
        InventoryMovementService::updateStockLevels($data);
        InventoryMovementService::updateStockLevels(array_replace($data, ["expected_minimum_stock" => 8]));
        $this->assertSame(1, DB::table("inventory_stock_alerts")->where("warehouse_item_id", $data["stock_id"])->where("status", "open")->count());
        $this->assertSame(0, DB::table("inventory_movements")->where("item_id", $data["item_id"])->count());

    }

    public function test_stale_edits_are_rejected_without_overwriting_either_value(): void {

        $data = $this->fixture();
        foreach([["expected_quantity" => 3], ["expected_minimum_stock" => 7]] as $stale) {

            $response = app(StockManagementController::class)->updateStockLevels($this->request(array_replace($data, $stale)));
            $this->assertSame(409, $response->status());
            $this->assertEquals(["quantity" => 4, "minimum_stock" => 6], $response->getData(true)["data"]);

        }
        $this->assertDatabaseHas("warehouse_items", ["item_id" => $data["item_id"], "quantity" => 4, "minimum_stock" => 6]);
        $this->assertSame(0, DB::table("inventory_movements")->where("item_id", $data["item_id"])->count());

    }

    public function test_minimum_can_change_without_replacing_an_existing_negative_balance(): void {

        $data = $this->fixture();
        DB::table("warehouse_items")->where("id", $data["stock_id"])->update(["quantity" => -2, "inventory_value" => -6]);
        $response = app(StockManagementController::class)->updateStockLevels($this->request(array_replace($data, [
            "quantity" => -2, "expected_quantity" => -2, "minimum_stock" => 1,
        ])));
        $this->assertSame(200, $response->status());
        $this->assertDatabaseHas("warehouse_items", ["item_id" => $data["item_id"], "quantity" => -2, "minimum_stock" => 1, "inventory_value" => -6]);
        $this->assertSame(0, DB::table("inventory_movements")->where("item_id", $data["item_id"])->count());

    }

    public function test_missing_inventory_rows_can_be_configured_without_a_fictitious_movement(): void {

        $data = $this->fixture();
        DB::table("inventory_stock_alerts")->where("warehouse_item_id", $data["stock_id"])->delete();
        DB::table("warehouse_items")->where("id", $data["stock_id"])->delete();
        InventoryMovementService::updateStockLevels(array_replace($data, ["quantity" => 0, "minimum_stock" => 3, "expected_quantity" => 0, "expected_minimum_stock" => 0]));
        $this->assertDatabaseHas("warehouse_items", ["item_id" => $data["item_id"], "quantity" => 0, "minimum_stock" => 3]);
        $this->assertSame(0, DB::table("inventory_movements")->where("item_id", $data["item_id"])->count());

    }

    public function test_validation_and_permissions_block_invalid_or_unauthorized_edits(): void {

        $data = $this->fixture();
        $rules = (new UpdateStockLevelsRequest())->rules();
        foreach([["quantity" => -1], ["minimum_stock" => -1], ["quantity" => ""], ["expected_quantity" => null]] as $invalid) {

            $this->assertTrue(Validator::make(array_replace($data, $invalid), $rules)->fails());

        }
        $roleId = DB::table("roles")->insertGetId([
            "slug" => "stock-read-only", "name" => "Solo lectura", "is_full_access" => false,
            "branch_scope_mode" => "all", "cash_register_scope_mode" => "all", "warehouse_scope_mode" => "all", "status" => "active",
        ]);
        auth()->user()->update(["role_id" => $roleId]);
        $this->actingAs(auth()->user()->fresh());
        try {

            app(StockManagementController::class)->updateStockLevels($this->request($data));
            $this->fail("La edición requiere permiso de operar inventario.");

        } catch(HttpException $exception) {

            $this->assertSame(403, $exception->getStatusCode());

        }
        $this->assertDatabaseHas("warehouse_items", ["item_id" => $data["item_id"], "quantity" => 4, "minimum_stock" => 6]);

    }

    public function test_an_operator_cannot_modify_a_warehouse_outside_their_scope(): void {

        $data = $this->fixture();
        $roleId = DB::table("roles")->insertGetId([
            "slug" => "stock-operator-limited", "name" => "Operador limitado", "is_full_access" => false,
            "branch_scope_mode" => "all", "cash_register_scope_mode" => "all", "warehouse_scope_mode" => "restricted", "status" => "active",
        ]);
        DB::table("role_sub_sections")->insert([
            "role_id" => $roleId,
            "sub_section_id" => DB::table("sub_sections")->where("dom_route", "stocks_management.stock.index")->value("id"),
            "actions" => json_encode(["view", "operate"]), "status" => "active",
        ]);
        auth()->user()->update(["role_id" => $roleId, "warehouse_scope_mode" => "inherit"]);
        $this->actingAs(auth()->user()->fresh());
        DB::table("user_warehouses")->where("user_id", auth()->id())->delete();
        AccessScopeService::clearUserCache((int) auth()->id());
        $this->assertTrue(RolePermissionService::canAccessRoute(auth()->user(), "stocks_management.levels.update", "POST"));
        try {

            app(StockManagementController::class)->updateStockLevels($this->request($data));
            $this->fail("El almacén debe estar dentro del alcance del operador.");

        } catch(HttpException $exception) {

            $this->assertSame(404, $exception->getStatusCode());

        }
        $this->assertDatabaseHas("warehouse_items", ["item_id" => $data["item_id"], "quantity" => 4, "minimum_stock" => 6]);

    }

    private function request(array $data): UpdateStockLevelsRequest {

        $request = UpdateStockLevelsRequest::create("/stocks_management/stock-levels", "POST", $data, [], [], ["HTTP_ACCEPT" => "application/json"]);
        $request->setContainer(app());
        $request->setRedirector(app("redirect"));
        $request->setUserResolver(fn() => auth()->user());
        $request->validateResolved();

        return $request;

    }

    private function fixture(): array {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $itemId = DB::table("items")->insertGetId([
            "internal_code" => "STOCK-EDIT", "name" => "Producto editable", "price" => 10,
            "currency_id" => DB::table("currencies")->value("id"),
            "measurement_unit_id" => DB::table("measurement_units")->value("id"), "type" => "product", "status" => "active",
        ]);
        $stockId = DB::table("warehouse_items")->insertGetId([
            "warehouse_id" => $warehouseId, "item_id" => $itemId, "quantity" => 4, "minimum_stock" => 6,
            "average_cost" => 3, "inventory_value" => 12, "status" => "active",
        ]);
        DB::table("inventory_stock_alerts")->insert([
            "warehouse_item_id" => $stockId, "quantity" => 4, "minimum_stock" => 6, "status" => "open", "detected_at" => now(),
        ]);

        return ["warehouse_id" => $warehouseId, "item_id" => $itemId, "stock_id" => $stockId, "quantity" => 10, "minimum_stock" => 2, "expected_quantity" => 4, "expected_minimum_stock" => 6];

    }

}
