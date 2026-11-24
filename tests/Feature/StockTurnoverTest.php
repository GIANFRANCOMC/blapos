<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\System\{Numbers};
use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class StockTurnoverTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    private const ROUTE = "analytics.stock_turnover_replenishment.";
    private const FROM = "2026-11-01";
    private const TO = "2026-11-03";

    private User $admin;
    private int $branchId;
    private int $warehouseId;
    private int $otherWarehouseId;
    private int $secondBranchId;
    private int $secondBranchWarehouseId;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();
        $this->withoutVite();
        $this->withoutMiddleware([ResolveTenant::class, TrustHosts::class, EnsureTenantSession::class, EnsureAuthenticatedSession::class]);
        $this->admin = User::query()->where("email", "admin@example.test")->firstOrFail();
        $this->branchId = (int) DB::table("branches")->where("name", "Sede Principal")->value("id");
        $this->warehouseId = (int) DB::table("warehouses")->where("branch_id", $this->branchId)->value("id");
        $this->otherWarehouseId = $this->warehouse($this->branchId, "Segundo almacén");
        $this->secondBranchId = DB::table("branches")->insertGetId(["company_id" => 1, "internal_code" => "ROT-2", "name" => "Sucursal secundaria"]);
        $this->secondBranchWarehouseId = $this->warehouse($this->secondBranchId, "Almacén de la segunda sucursal");

    }

    public function test_orders_replenishment_by_location_and_respects_the_current_minimum(): void {

        $product = $this->product("Producto repetido");
        $this->stock($product, $this->warehouseId, "0", "0");
        $this->stock($product, $this->otherWarehouseId, "20", "2");
        $this->stock($this->product("Al mínimo"), $this->warehouseId, "3.125", "3.125");
        $this->stock($this->product("Descontinuado", status: "inactive"), $this->warehouseId, "0", "4");
        $this->stock($this->product("Servicio", type: "service"), $this->warehouseId, "0", "4");
        $this->stock($this->product("Posición inactiva"), $this->warehouseId, "0", "4", "inactive");
        $this->stock($this->product("Almacén inactivo"), $this->warehouse($this->branchId, "Cerrado", "inactive"), "0", "4");

        $this->actingAs($this->admin)->get(route(self::ROUTE."index"))->assertOk();
        $this->getJson($this->listUrl(["branch_id" => $this->branchId]))
            ->assertOk()->assertJsonPath("data.total", 3)->assertJsonPath("data.per_page", 10)
            ->assertJsonPath("data.data.0.product.name", "Producto repetido")
            ->assertJsonPath("data.data.0.stock_status", "empty")->assertJsonPath("data.data.0.minimum_stock", null)
            ->assertJsonPath("data.data.1.stock_status", "low")
            ->assertJsonPath("data.data.1.minimum_stock", Numbers::storage("3.125"))
            ->assertJsonPath("data.data.2.stock_status", "healthy");
        $this->getJson($this->listUrl(["replenishment_only" => 1]))
            ->assertOk()->assertJsonPath("data.total", 2);
        $this->getJson($this->listUrl(["warehouse_id" => $this->otherWarehouseId, "replenishment_only" => 1]))
            ->assertOk()->assertJsonPath("data.total", 0);

    }

    public function test_counts_only_net_commercial_movements_on_the_inclusive_kardex_dates(): void {

        $product = $this->product("Insumo");
        $this->stock($product, $this->warehouseId, "8", "2");
        $this->stock($product, $this->otherWarehouseId, "11", "5");
        $this->movement($product, $this->warehouseId, "sale", "-3.125", self::FROM." 00:00:00");
        $this->movement($product, $this->warehouseId, "sale_delivery", "-4", "2026-11-02 12:03:00");
        $this->movement($product, $this->warehouseId, "recipe_sale", "-1.250", self::TO." 23:59:59");
        $this->movement($product, $this->warehouseId, "customer_return", "0.500", self::TO." 12:00:00");
        $this->movement($product, $this->warehouseId, "sale_cancellation", "1", self::TO." 23:15:00");
        $this->movement($product, $this->warehouseId, "sale", "-100", "2026-11-04 00:00:00");
        $this->movement($product, $this->warehouseId, "sale_delivery", "-100", "2026-10-31 23:59:59");
        foreach(["transfer_out", "manual", "supplier_return", "physical_count", "recipe_waste"] as $origin) {
            $this->movement($product, $this->warehouseId, $origin, "-50", self::FROM." 10:00:00");
        }
        $this->movement($product, $this->otherWarehouseId, "customer_return", "2", self::FROM." 10:00:00");

        $this->actingAs($this->admin)->getJson($this->listUrl(["warehouse_id" => $this->warehouseId]))
            ->assertOk()->assertJsonPath("data.total", 1)
            ->assertJsonPath("data.data.0.quantity", Numbers::storage("8"))
            ->assertJsonPath("data.data.0.net_outgoing", Numbers::storage("6.875"));
        $this->getJson($this->listUrl(["warehouse_id" => $this->otherWarehouseId]))
            ->assertOk()->assertJsonPath("data.data.0.net_outgoing", Numbers::storage("-2"));

    }

    public function test_respects_view_permissions_and_intersected_operational_scopes(): void {

        $product = $this->product("Producto con stock");
        foreach([$this->warehouseId, $this->otherWarehouseId, $this->secondBranchWarehouseId] as $warehouse) {
            $this->stock($product, $warehouse, "5", "1");
            $this->movement($product, $warehouse, "sale", "-".$warehouse, self::FROM." 10:00:00");
        }

        $roleId = DB::table("roles")->insertGetId([
            "slug" => "rotacion-reader", "name" => "Perfil de reposición", "is_full_access" => false,
            "branch_scope_mode" => "restricted", "warehouse_scope_mode" => "restricted", "status" => "active",
        ]);
        DB::table("role_branches")->insert(["role_id" => $roleId, "branch_id" => $this->branchId]);
        DB::table("role_warehouses")->insert([
            ["role_id" => $roleId, "warehouse_id" => $this->warehouseId],
            ["role_id" => $roleId, "warehouse_id" => $this->secondBranchWarehouseId],
        ]);
        DB::table("role_sub_sections")->insert(["role_id" => $roleId, "sub_section_id" => 110, "actions" => '["view"]', "status" => "active"]);
        $this->admin->update(["role_id" => $roleId]);
        $this->actingAs($this->admin->fresh());
        $this->getJson(route(self::ROUTE."index"))->assertForbidden();
        $this->getJson(route(self::ROUTE."initParams"))->assertForbidden();
        $this->getJson($this->listUrl())->assertForbidden();

        DB::table("role_sub_sections")->insert(["role_id" => $roleId, "sub_section_id" => 111, "actions" => '["view"]', "status" => "active"]);
        CompanySectionService::clearTenantCache();
        $this->getJson(route(self::ROUTE."initParams"))->assertOk()
            ->assertJsonCount(1, "data.warehouses")->assertJsonPath("data.warehouses.0.id", $this->warehouseId)
            ->assertJsonCount(1, "data.branches")->assertJsonPath("data.branches.0.id", $this->branchId);
        $this->getJson($this->listUrl())->assertOk()->assertJsonPath("data.total", 1)
            ->assertJsonPath("data.data.0.net_outgoing", Numbers::storage($this->warehouseId));
        $this->getJson($this->listUrl(["warehouse_id" => $this->otherWarehouseId]))->assertForbidden();
        $this->getJson($this->listUrl(["warehouse_id" => $this->secondBranchWarehouseId]))->assertForbidden();
        $this->getJson($this->listUrl(["branch_id" => $this->secondBranchId]))->assertForbidden();

        DB::table("role_warehouses")->where("role_id", $roleId)->delete();
        AccessScopeService::clearUserCache($this->admin->id);
        $this->getJson($this->listUrl())->assertOk()->assertJsonPath("data.total", 0);

    }

    public function test_validates_dates_and_rejects_mismatched_warehouse_and_branch_even_for_admins(): void {

        $this->actingAs($this->admin);
        $this->getJson($this->listUrl(["branch_id" => $this->secondBranchId, "warehouse_id" => $this->warehouseId]))
            ->assertUnprocessable()->assertJsonValidationErrors("warehouse_id");
        $this->getJson($this->listUrl(["to" => "2026-10-31"]))
            ->assertUnprocessable()->assertJsonValidationErrors("to");
        $this->getJson($this->listUrl(["to" => "2028-01-01"]))
            ->assertUnprocessable()->assertJsonValidationErrors("to");

    }

    private function listUrl(array $filters = []): string {

        return route(self::ROUTE."list", $filters + ["from" => self::FROM, "to" => self::TO]);

    }

    private function warehouse(int $branchId, string $name, string $status = "active"): int {

        return DB::table("warehouses")->insertGetId(["branch_id" => $branchId, "name" => $name, "status" => $status]);

    }

    private function product(string $name, string $type = "product", string $status = "active"): int {

        return DB::table("items")->insertGetId([
            "internal_code" => $name,
            "name" => $name,
            "price" => 1,
            "currency_id" => DB::table("currencies")->where("code", "PEN")->value("id"),
            "type" => $type,
            "status" => $status,
        ]);

    }

    private function stock(int $item, int $warehouse, string $quantity, string $minimum, string $status = "active"): void {

        DB::table("warehouse_items")->insert([
            "item_id" => $item, "warehouse_id" => $warehouse, "quantity" => $quantity,
            "minimum_stock" => $minimum, "status" => $status,
        ]);

    }

    private function movement(int $item, int $warehouse, string $origin, string $quantity, string $createdAt): void {

        DB::table("inventory_movements")->insert([
            "item_id" => $item, "warehouse_id" => $warehouse, "origin_type" => $origin,
            "movement_type" => str_starts_with($quantity, "-") ? "exit" : "entry",
            "quantity_before" => 100, "quantity_change" => $quantity, "quantity_after" => 100 + (float) $quantity,
            "reason" => "Movimiento de prueba", "created_at" => $createdAt,
        ]);

    }

}
