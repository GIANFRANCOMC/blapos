<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Organizations\{User};
use App\Services\System\Catalogs\Products\{ProductService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{Auth, DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class ProductCapacityBoundaryTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_product_creation_and_update_ignore_capacity_and_preserve_sale_availability(): void {

        $userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");
        $warehouseId = (int) DB::table("warehouses")->value("id");
        $inventory = [["warehouse_id" => $warehouseId, "initial_stock" => 2, "minimum_stock" => 0]];

        $product = ProductService::create([
            "internal_code" => "PRODUCT-CAPACITY-TEST",
            "barcode" => "5901234123457",
            "name" => "Producto físico",
            "price" => 10,
            "currency_id" => $currencyId,
            "status" => "active",
            "capacity_control_enabled" => true,
            "capacity_limit" => 1,
            "inventory" => $inventory,
        ], $userId);

        $this->assertNotNull($product);
        $this->assertFalse($product->hasCapacityControl());
        $this->assertNull($product->capacity_limit);
        $this->assertSame(0, $product->capacity_used);

        $product->forceFill([
            "capacity_control_enabled" => true,
            "capacity_limit" => 1,
            "capacity_used" => 1,
        ])->save();

        $this->assertTrue($product->fresh()->isAvailableForSale());
        $this->assertTrue(Item::query()->availableForSale()->whereKey($product->id)->exists());

        $updated = ProductService::update($product->fresh(), [
            "name" => "Producto físico actualizado",
            "inventory" => [["warehouse_id" => $warehouseId, "minimum_stock" => 0]],
        ], $userId);

        $this->assertFalse($updated->capacity_control_enabled);
        $this->assertNull($updated->capacity_limit);
        $this->assertSame(0, $updated->capacity_used);

    }

    public function test_product_endpoint_rejects_capacity_fields(): void {

        $user = User::query()->where("email", "admin@example.test")->firstOrFail();
        Auth::login($user);

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ]);

        $this->postJson(route("products.store"), [
            "internal_code" => "PRODUCT-HTTP-TEST",
            "barcode" => "5901234123457",
            "name" => "Producto físico",
            "price" => 10,
            "currency_id" => (int) DB::table("currencies")->value("id"),
            "commission_type" => "none",
            "see_my_web" => true,
            "see_my_web_price" => false,
            "inventory" => [[
                "warehouse_id" => (int) DB::table("warehouses")->value("id"),
                "initial_stock" => 0,
                "minimum_stock" => 0,
            ]],
            "status" => "active",
            "capacity_control_enabled" => true,
            "capacity_limit" => 5,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(["capacity_control_enabled", "capacity_limit"]);

    }
}
