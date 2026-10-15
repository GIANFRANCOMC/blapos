<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Catalogs\References\{CatalogReferenceListExport, CatalogReferenceTemplateExport};
use App\Imports\System\Catalogs\References\{CatalogReferenceImport};
use App\Models\System\Catalogs\{Brand, Category};
use App\Services\Guest\{GuestCatalogService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
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
        $this->assertCount(5, $export->headings());
        $this->assertSame(["Nombre", "Código interno", "Descripción", "Sitio oficial", "Estado"], (new CatalogReferenceTemplateExport("brands"))->headings());

        $exportedBrand = $export->query()->firstOrFail();

        $this->assertCount(5, $export->map($exportedBrand));

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
