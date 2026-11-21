<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Catalogs\Products\ProductListExport;
use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureOperationalScope, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Catalogs\Item;
use App\Models\System\Organizations\User;
use App\Services\System\Catalogs\Products\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ProvisionsSystemDatabase;
use Tests\TestCase;

final class ProductListingAndDateTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->provisionSystemDatabase();
        $this->actingAs(User::query()->where('email', 'admin@example.test')->firstOrFail());
        $this->withoutMiddleware([ResolveTenant::class, TrustHosts::class, EnsureTenantSession::class, EnsureAuthenticatedSession::class, EnsureOperationalScope::class]);
    }

    private function payload(): array {
        return [
            'internal_code' => 'PROD-DATE-TEST', 'barcode' => '5901234123457', 'name' => 'Producto de prueba',
            'price' => 10, 'currency_id' => (int) DB::table('currencies')->value('id'), 'commission_type' => 'none',
            'see_my_web' => true, 'see_my_web_price' => false, 'status' => 'active',
            'inventory' => [['warehouse_id' => (int) DB::table('warehouses')->value('id'), 'initial_stock' => 0, 'minimum_stock' => 0]],
        ];
    }

    public function test_creation_rejects_yesterday_but_accepts_today_and_editing_keeps_past_dates(): void {
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(12, 0));
        $payload = $this->payload();
        $this->postJson(route('products.store'), [...$payload, 'expires_at' => '2026-11-19'])
            ->assertUnprocessable()->assertJsonValidationErrors('expires_at');
        $this->assertFalse(Item::query()->where('internal_code', $payload['internal_code'])->exists());
        $this->postJson(route('products.store'), [...$payload, 'expires_at' => '2026-11-20'])->assertSuccessful();
        $product = Item::query()->where('internal_code', $payload['internal_code'])->firstOrFail();
        $this->patchJson(route('products.update', $product->id), [...$payload, 'expires_at' => '2026-11-01'])->assertSuccessful();
        $this->assertSame('2026-11-01', $product->fresh()->expires_at->toDateString());
        $this->travelBack();
    }

    public function test_creation_keeps_expiration_optional_and_accepts_future_dates(): void {
        $this->travelTo(now()->setDate(2026, 11, 20)->setTime(12, 0));
        $payload = $this->payload();
        $this->postJson(route('products.store'), [...$payload, 'expires_at' => null])->assertSuccessful();
        $product = Item::query()->where('internal_code', $payload['internal_code'])->firstOrFail();
        $this->assertNull($product->expires_at);
        $rules = (new \App\Http\Requests\System\Catalogs\Products\StoreProductRequest())->rules();
        $this->assertTrue(validator(['expires_at' => '2026-11-21'], ['expires_at' => $rules['expires_at']])->passes());
        $this->travelBack();
    }

    public function test_name_order_is_global_stable_filtered_and_shared_with_export(): void {
        $payload = $this->payload();
        $userId = (int) auth()->id();
        foreach(['Orden C', 'Orden A', 'Orden B', 'Orden A'] as $i => $name) {
            ProductService::create([...$payload, 'internal_code' => 'PROD-ORDER-'.$i, 'barcode' => null, 'name' => $name], $userId);
        }
        $filters = ['filter_by' => 'name', 'word' => 'Orden', 'sort_direction' => 'desc'];
        $expected = ProductService::getFilteredListQuery($filters)->pluck('id')->all();
        $this->assertSame(['Orden C', 'Orden B', 'Orden A', 'Orden A'], ProductService::getFilteredListQuery($filters)->pluck('name')->all());
        $this->assertGreaterThan($expected[3], $expected[2]);
        $page1 = $this->getJson(route('products.list', [...$filters, 'per_page' => 2]))->assertOk();
        $page2 = $this->getJson($page1->json('next_page_url'))->assertOk();
        $this->assertSame($expected, [...array_column($page1->json('data'), 'id'), ...array_column($page2->json('data'), 'id')]);
        $this->assertSame($expected, (new ProductListExport($filters))->query()->pluck('id')->all());
        $this->getJson(route('products.list', ['filter_by' => 'name', 'word' => 'Orden']))
            ->assertOk()->assertJsonPath('data.0.name', 'Orden A');
    }

    public function test_invalid_order_is_rejected_before_listing_or_exporting(): void {
        foreach(['products.list', 'products.export'] as $route) {
            $this->getJson(route($route, ['sort_direction' => 'desc; drop table items']))
                ->assertUnprocessable()->assertJsonValidationErrors('sort_direction');
        }
    }
}
