<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Warehouses\{PhysicalCountTemplateExport};
use App\Imports\System\Warehouses\{PhysicalCountPreviewImport};
use App\Services\System\Catalogs\Products\{ProductService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\{Excel as ExcelFormat};
use Maatwebsite\Excel\Facades\{Excel};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class PhysicalCountPreviewTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_template_and_preview_do_not_change_stock_before_save(): void {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");
        $product = ProductService::create([
            "internal_code" => "PHYSICAL-COUNT-TEST",
            "barcode" => "0123456789012",
            "name" => "Producto para toma física",
            "price" => 10,
            "currency_id" => $currencyId,
            "status" => "active",
            "inventory" => [["warehouse_id" => $warehouseId, "initial_stock" => 4, "minimum_stock" => 0]],
        ], $userId);

        $this->assertNotNull($product);

        $template = new PhysicalCountTemplateExport($warehouseId);
        $rows = $template->query()->get()->map(fn($record): array => $template->map($record));
        $templateRow = $rows->first(fn(array $row): bool => $row[0] === "PHYSICAL-COUNT-TEST");

        $this->assertSame("0123456789012", $templateRow[1]);
        $this->assertSame("", $templateRow[4]);
        $this->assertStringStartsWith("PK", Excel::raw($template, ExcelFormat::XLSX));

        $preview = new PhysicalCountPreviewImport($warehouseId);
        $preview->collection(collect([
            collect([
                "codigo_interno" => "PHYSICAL-COUNT-TEST",
                "codigo_de_barras" => "0123456789012",
                "saldo_contado" => "2,5",
            ]),
        ]));

        $this->assertSame($product->id, $preview->items()[0]["item_id"]);
        $this->assertSame("2.5", $preview->items()[0]["resulting_balance"]);
        $this->assertEquals(4, DB::table("warehouse_items")
            ->where("warehouse_id", $warehouseId)
            ->where("item_id", $product->id)
            ->value("quantity"));

    }

    public function test_preview_rejects_duplicate_products_and_invalid_balances(): void {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");

        ProductService::create([
            "internal_code" => "PHYSICAL-DUPLICATE-TEST",
            "name" => "Producto repetido",
            "price" => 10,
            "currency_id" => $currencyId,
            "status" => "active",
            "inventory" => [["warehouse_id" => $warehouseId, "initial_stock" => 0, "minimum_stock" => 0]],
        ], $userId);

        try {

            (new PhysicalCountPreviewImport($warehouseId))->collection(collect([
                collect(["codigo_interno" => "PHYSICAL-DUPLICATE-TEST", "saldo_contado" => "1"]),
                collect(["codigo_interno" => "PHYSICAL-DUPLICATE-TEST", "saldo_contado" => "2"]),
            ]));
            $this->fail("Un producto repetido no debe llegar a la vista previa.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Fila 3", $exception->errors()["file"][0]);

        }

        try {

            (new PhysicalCountPreviewImport($warehouseId))->collection(collect([
                collect(["codigo_interno" => "PHYSICAL-DUPLICATE-TEST", "saldo_contado" => "-1"]),
            ]));
            $this->fail("Un saldo negativo no debe llegar a la vista previa.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Saldo contado", $exception->errors()["file"][0]);

        }

    }

}
