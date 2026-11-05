<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Catalogs\References\{CatalogReferenceListExport, CatalogReferenceTemplateExport};
use App\Imports\System\Catalogs\References\{CatalogReferenceImport};
use App\Models\System\Catalogs\{Brand, Category};
use App\Services\System\Catalogs\Brands\BrandService;
use App\Services\System\Catalogs\Categories\CategoryService;
use App\Services\System\Catalogs\CatalogUsageService;
use App\Services\Guest\{GuestCatalogService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Tenancy\TenantCompanyContext;
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\{ValidationException};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class CatalogReferenceTransferTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_catalog_usage_counts_active_items_by_type_and_respects_company_modules(): void {

        $category = Category::query()->create(["internal_code" => "USAGE-CATEGORY", "name" => "Uso de prueba", "status" => "active"]);
        $brand = Brand::query()->create(["internal_code" => "USAGE-BRAND", "name" => "Marca de prueba", "status" => "active"]);
        $currencyId = (int) DB::table("currencies")->where("code", "PEN")->value("id");

        foreach(["product", "service", "subscription"] as $type) {

            $itemId = DB::table("items")->insertGetId([
                "brand_id" => $type === "product" ? $brand->id : null,
                "internal_code" => "USAGE-".strtoupper($type),
                "name" => "Elemento {$type}",
                "price" => 10,
                "currency_id" => $currencyId,
                "type" => $type,
                "status" => "active",
            ]);

            DB::table("category_items")->insert(["category_id" => $category->id, "item_id" => $itemId, "status" => "active"]);

        }

        $listedCategory = CategoryService::getFilteredListQuery()->whereKey($category->id)->firstOrFail();
        $listedBrand = BrandService::getFilteredListQuery()->whereKey($brand->id)->firstOrFail();

        $this->assertSame(1, $listedCategory->active_products_count);
        $this->assertSame(1, $listedCategory->active_services_count);
        $this->assertSame(1, $listedCategory->active_subscriptions_count);
        $this->assertSame(1, $listedBrand->active_products_count);
        $this->assertSame(0, $listedBrand->active_services_count);
        $this->assertSame(0, $listedBrand->active_subscriptions_count);

        $moduleId = (int) DB::table("sub_sections")->where("dom_route", "services.index")->value("id");
        DB::table("companies_sub_sections")
            ->where("company_id", app(TenantCompanyContext::class)->id())
            ->where("sub_section_id", $moduleId)
            ->update(["status" => "inactive"]);

        $this->assertNotContains("service", CatalogUsageService::enabledTypes());

    }

    public function test_brand_import_uses_defaults_and_export_uses_list_filters(): void {

        $import = new CatalogReferenceImport("brands", $this->userId());

        $import->collection(collect([
            collect(["nombre" => "Marca lote uno", "codigo_interno" => "", "descripcion" => "Primera marca", "sitio_oficial" => "https://marca.example.test", "estado" => "Activo"]),
            collect(["nombre" => "Marca lote dos", "codigo_interno" => "LOTE-DOS", "estado" => "Inactivo"]),
        ]));

        $this->assertSame(2, $import->importedCount());
        $this->assertNotEmpty(Brand::query()->where("name", "Marca lote uno")->value("internal_code"));
        $this->assertSame("https://marca.example.test", Brand::query()->where("name", "Marca lote uno")->value("website_url"));
        $this->assertSame("inactive", Brand::query()->where("name", "Marca lote dos")->value("status"));
        $this->assertFalse(Schema::hasColumn("brands", "logo_path"));
        $this->assertFalse(Schema::hasColumn("brands", "origin_country_code"));

        $export = new CatalogReferenceListExport("brands", ["filter_by" => "name", "word" => "Marca lote dos"]);

        $this->assertSame(1, $export->query()->count());
        $this->assertCount(6, $export->headings());
        $this->assertSame(["Nombre", "Código interno", "Descripción", "Sitio oficial", "Estado"], (new CatalogReferenceTemplateExport("brands"))->headings());

        $exportedBrand = $export->query()->firstOrFail();

        $this->assertCount(6, $export->map($exportedBrand));
        $this->assertSame(0, $exportedBrand->active_items_count);

        $brand = Brand::query()->where("name", "Marca lote uno")->firstOrFail();
        $currencyId = (int) DB::table("currencies")->where("code", "PEN")->value("id");

        foreach(["active", "inactive"] as $status) {

            DB::table("items")->insert([
                "brand_id" => $brand->id,
                "internal_code" => "TEST-BRAND-".strtoupper($status),
                "name" => "Producto {$status}",
                "price" => 10,
                "currency_id" => $currencyId,
                "type" => "product",
                "status" => $status,
            ]);

        }

        $listedBrand = BrandService::getFilteredListQuery()->whereKey($brand->id)->firstOrFail();
        $exportedBrand = (new CatalogReferenceListExport("brands"))->query()->whereKey($brand->id)->firstOrFail();

        $this->assertSame(1, $listedBrand->active_items_count);
        $this->assertSame(1, $exportedBrand->active_items_count);
        $this->assertSame(1, $export->map($exportedBrand)[4]);

    }

    public function test_category_import_uses_reduced_columns_and_rolls_back_invalid_batch(): void {

        $import = new CatalogReferenceImport("categories", $this->userId());

        $import->collection(collect([
            collect(["nombre" => "Categoría lote base", "codigo_interno" => "", "estado" => "Activa"]),
        ]));

        $category = Category::query()->where("name", "Categoría lote base")->firstOrFail();

        $this->assertSame(1, $import->importedCount());
        $this->assertFalse(Schema::hasColumn("categories", "is_public"));
        $this->assertFalse(Schema::hasColumn("categories", "sort_order"));
        $this->assertCount(5, (new CatalogReferenceListExport("categories"))->headings());
        $this->assertSame(["Nombre", "Código interno", "Descripción", "Estado"], (new CatalogReferenceTemplateExport("categories"))->headings());
        $this->assertTrue(GuestCatalogService::publicCategories()->contains("id", $category->id));

        $exportedCategory = (new CatalogReferenceListExport("categories"))->query()->whereKey($category->id)->firstOrFail();

        $this->assertCount(5, (new CatalogReferenceListExport("categories"))->map($exportedCategory));

        try {

            (new CatalogReferenceImport("categories", $this->userId()))->collection(collect([
                collect(["nombre" => "Categoría transitoria", "codigo_interno" => "TRANSITORIA-1"]),
                collect(["nombre" => "Categoría transitoria", "codigo_interno" => "TRANSITORIA-2"]),
            ]));

            $this->fail("La carga con nombres duplicados debe rechazarse.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Fila 3", $exception->errors()["file"][0]);

        }

        $this->assertFalse(Category::query()->where("name", "Categoría transitoria")->exists());

        $this->assertSame("import", RolePermissionService::actionForRoute("products.import.template", "GET"));
        $this->assertSame("import", RolePermissionService::actionForRoute("categories.import.template", "GET"));
        $this->assertSame("import", RolePermissionService::actionForRoute("brands.import.template", "GET"));
        $this->assertSame("export", RolePermissionService::actionForRoute("categories.export", "GET"));
        $this->assertSame("export", RolePermissionService::actionForRoute("brands.export", "GET"));

    }

    private function userId(): int {

        return (int) DB::table("users")->where("email", "admin@example.test")->value("id");

    }

}
