<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Imports\System\Catalogs\Products\ProductBasicImport;
use App\Models\System\Catalogs\Item;
use App\Models\System\General\MeasurementUnit;
use App\Models\System\Warehouses\WarehouseItem;
use App\Services\System\Catalogs\Products\ProductService;
use App\Services\System\Base\InternalCodeService;
use Database\Seeders\CommercialCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
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

        $this->assertSame("PROD-000001", InternalCodeService::applyPrefix("product", "PROD-000001"));
        $this->assertSame("SERV-000001", InternalCodeService::applyPrefix("service", "SERV-000001"));
        $this->assertSame("MEMB-00001", InternalCodeService::applyPrefix("subscription", "MEMB-00001"));
        $this->assertSame(10, Item::query()->where("type", Item::TYPE_PRODUCT)->count());
        $this->assertSame(2, Item::query()->where("type", Item::TYPE_SERVICE)->count());
        $this->assertSame(5, Item::query()->where("type", Item::TYPE_SUBSCRIPTION)->count());
        $this->assertSame(10, Item::query()->where("type", Item::TYPE_PRODUCT)->where("measurement_unit_id", 1)->count());
        $this->assertSame(0, Item::query()->whereIn("type", [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION])
            ->whereNotNull("measurement_unit_id")
            ->count());
        $this->assertSame(0, Item::query()->whereIn("type", [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION])
            ->whereNotNull("barcode")
            ->count());
        $this->assertSame(100, DB::table("brands")->count());
        $this->assertSame(100, DB::table("categories")->count());
        $this->assertSame(10, Item::query()->where("type", Item::TYPE_PRODUCT)->whereNotNull("brand_id")->count());
        $this->assertSame(0, Item::query()->whereIn("type", [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION])
            ->whereNotNull("brand_id")
            ->count());
        $this->assertSame(17, DB::table("category_items")->count());
        $this->assertSame(1, DB::table("category_items")
            ->join("items", "items.id", "=", "category_items.item_id")
            ->join("categories", "categories.id", "=", "category_items.category_id")
            ->where("items.internal_code", "PROD-000001")
            ->where("categories.name", "like", "Minería - %")
            ->count());

    }

    public function test_default_catalog_creates_all_items_without_duplicating_inventory(): void {

        $seeder = app(CommercialCatalogSeeder::class);
        $seeder->run();

        $this->assertSame(10000, Item::query()->where("type", Item::TYPE_PRODUCT)->count());
        $this->assertSame(10000, Item::query()->where("type", Item::TYPE_SERVICE)->count());
        $this->assertSame(100, Item::query()->where("type", Item::TYPE_SUBSCRIPTION)->count());
        $this->assertSame(10000, Item::query()->where("type", Item::TYPE_PRODUCT)->whereNotNull("measurement_unit_id")->count());
        $this->assertSame(0, Item::query()->whereIn("type", [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION])
            ->whereNotNull("measurement_unit_id")
            ->count());
        $this->assertSame(10000, Item::query()->where("type", Item::TYPE_PRODUCT)->distinct()->count("barcode"));
        $this->assertSame(100, DB::table("brands")->count());
        $this->assertSame(100, DB::table("categories")->count());
        $this->assertSame(100, Item::query()->where("type", Item::TYPE_PRODUCT)->distinct()->count("brand_id"));
        $this->assertSame(100, DB::table("category_items")->distinct()->count("category_id"));
        $this->assertSame(20100, DB::table("category_items")->count());
        $emptyCount = WarehouseItem::query()->where("quantity", 0)->count();
        $lowCount = WarehouseItem::query()
            ->where("quantity", ">", 0)
            ->whereColumn("quantity", "<=", "minimum_stock")
            ->count();
        $healthyCount = WarehouseItem::query()->whereColumn("quantity", ">", "minimum_stock")->count();
        $movementCount = DB::table("inventory_movements")
            ->where("origin_type", "commercial_catalog_seeder")
            ->count();
        $alertCount = DB::table("inventory_stock_alerts")->where("status", "open")->count();

        $this->assertGreaterThan(0, $emptyCount);
        $this->assertGreaterThan(0, $lowCount);
        $this->assertGreaterThan(0, $healthyCount);
        $this->assertGreaterThan(0, WarehouseItem::query()->where("minimum_stock", ">", 0)->count());
        $this->assertSame(WarehouseItem::query()->where("quantity", ">", 0)->count(), $movementCount);
        $this->assertSame(WarehouseItem::query()
            ->where("minimum_stock", ">", 0)
            ->whereColumn("quantity", "<=", "minimum_stock")
            ->count(), $alertCount);
        $this->assertDatabaseHas("items", [
            "type" => Item::TYPE_PRODUCT,
            "internal_code" => "PROD-000001",
            "barcode" => "2000000000015",
            "name" => "Casco de seguridad Andes industrial S - Ref. 00001",
            "price" => 952.289,
        ]);

        $stock = WarehouseItem::query()->firstOrFail();
        $stock->update(["quantity" => 1]);
        $seeder->run();

        $this->assertSame(20100, Item::query()->count());
        $this->assertSame(100, DB::table("brands")->count());
        $this->assertSame(100, DB::table("categories")->count());
        $this->assertSame(20100, DB::table("category_items")->count());
        $this->assertSame($movementCount, DB::table("inventory_movements")
            ->where("origin_type", "commercial_catalog_seeder")
            ->count());
        $this->assertSame($alertCount, DB::table("inventory_stock_alerts")->where("status", "open")->count());
        $this->assertSame(1.0, (float) $stock->fresh()->quantity);

    }

    public function test_catalog_rejects_barcodes_owned_by_other_products(): void {

        $legacy = ProductService::create([
            "internal_code" => "PRO-LEGACY",
            "barcode" => "2000000000015",
            "name" => "Producto existente",
            "price" => 10,
            "currency_id" => (int) DB::table("currencies")->value("id"),
            "inventory" => [],
        ], (int) DB::table("users")->value("id"));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Un código de barras del CSV ya pertenece a otro producto.");

        try {

            app(CommercialCatalogSeeder::class)->run(10, 0, 0);

        }finally {

            $this->assertDatabaseHas("items", [
                "id" => $legacy->id,
                "internal_code" => "PRO-LEGACY",
                "name" => "Producto existente",
            ]);

        }

    }

}
