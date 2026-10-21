<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Imports\System\Catalogs\Products\ProductBasicImport;
use App\Models\System\Catalogs\Item;
use App\Models\System\General\MeasurementUnit;
use App\Models\System\Warehouses\WarehouseItem;
use App\Services\System\Catalogs\Products\ProductService;
use Database\Seeders\CommercialCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ProvisionsSystemDatabase;
use Tests\TestCase;

final class MeasurementUnitCatalogTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_manual_and_imported_products_use_the_only_measurement_unit(): void {

        $this->assertSame(1, MeasurementUnit::query()->count());
        $this->assertDatabaseHas("measurement_units", [
            "id" => 1,
            "code" => "UNIT",
            "singular_name" => "UNIDAD",
            "plural_name" => "UNIDADES",
        ]);

        $userId = (int) DB::table("users")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");
        $warehouseId = (int) DB::table("warehouses")->value("id");

        $product = ProductService::create([
            "internal_code" => "PRO-UNIT-TEST",
            "name" => "Producto con unidad",
            "price" => 10,
            "currency_id" => $currencyId,
            "inventory" => [],
        ], $userId);

        $this->assertSame(MeasurementUnit::defaultId(), $product->measurement_unit_id);
        $this->assertSame("UNIDADES", $product->measurementUnit->plural_name);

        $import = new ProductBasicImport($currencyId, $warehouseId, $userId);
        $import->collection(collect([collect([
            "codigo_interno" => "IMPORTED-UNIT-TEST",
            "nombre" => "Producto importado",
            "precio" => 15,
            "stock_inicial" => 0,
        ])]));

        $this->assertSame(1, $import->importedCount());
        $this->assertSame(1, (int) Item::query()
            ->where("name", "Producto importado")
            ->value("measurement_unit_id"));

    }

    public function test_catalog_accepts_smaller_explicit_quantities(): void {

        app(CommercialCatalogSeeder::class)->run(10, 2, 5);

        $this->assertSame(10, Item::query()->where("type", Item::TYPE_PRODUCT)->count());
        $this->assertSame(2, Item::query()->where("type", Item::TYPE_SERVICE)->count());
        $this->assertSame(5, Item::query()->where("type", Item::TYPE_SUBSCRIPTION)->count());
        $this->assertSame(10, Item::query()->where("type", Item::TYPE_PRODUCT)->where("measurement_unit_id", 1)->count());
        $this->assertSame(0, Item::query()->whereIn("type", [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION])
            ->whereNotNull("measurement_unit_id")
            ->count());

    }

    public function test_default_catalog_creates_all_items_without_duplicating_inventory(): void {

        $seeder = app(CommercialCatalogSeeder::class);
        $seeder->run();

        $this->assertSame(3000, Item::query()->where("type", Item::TYPE_PRODUCT)->count());
        $this->assertSame(1000, Item::query()->where("type", Item::TYPE_SERVICE)->count());
        $this->assertSame(100, Item::query()->where("type", Item::TYPE_SUBSCRIPTION)->count());
        $this->assertSame(3000, Item::query()->where("type", Item::TYPE_PRODUCT)->whereNotNull("measurement_unit_id")->count());
        $this->assertSame(0, Item::query()->whereIn("type", [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION])
            ->whereNotNull("measurement_unit_id")
            ->count());
        $this->assertSame(3000, Item::query()->where("type", Item::TYPE_PRODUCT)->distinct()->count("barcode"));
        $this->assertSame(3000, DB::table("inventory_movements")
            ->where("origin_type", "commercial_catalog_seeder")
            ->count());

        $stock = WarehouseItem::query()->firstOrFail();
        $stock->update(["quantity" => 1]);
        $seeder->run();

        $this->assertSame(4100, Item::query()->count());
        $this->assertSame(3000, DB::table("inventory_movements")
            ->where("origin_type", "commercial_catalog_seeder")
            ->count());
        $this->assertSame(1.0, (float) $stock->fresh()->quantity);

    }

}
