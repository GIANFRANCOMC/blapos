<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Warehouses\{InventoryOperationTemplateExport, InventoryReportExport};
use App\Imports\System\Warehouses\{InventoryOperationPreviewImport};
use App\Services\System\Warehouses\StockManagement\{StockManagementService};
use App\Support\System\Warehouses\{InventoryOperationTemplate};
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

        $template = new InventoryOperationTemplateExport($warehouseId, "physical_count");
        $rows = $template->query()->get()->map(fn($record): array => $template->map($record));
        $templateRow = $rows->first(fn(array $row): bool => $row[0] === "PHYSICAL-COUNT-TEST");

        $this->assertSame("0123456789012", $templateRow[1]);
        $this->assertSame("UNIDADES", $templateRow[3]);
        $this->assertSame("", $templateRow[5]);
        $this->assertStringStartsWith("PK", Excel::raw($template, ExcelFormat::XLSX));

        $preview = new InventoryOperationPreviewImport($warehouseId, "physical_count");
        $preview->collection(collect([
            collect([
                "codigo_interno" => "PHYSICAL-COUNT-TEST",
                "codigo_de_barras" => "0123456789012",
                "saldo_contado" => "2,5",
            ]),
        ]));

        $this->assertSame($product->id, $preview->items()[0]["item_id"]);
        $this->assertSame("2.5", $preview->items()[0]["amount"]);
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

            (new InventoryOperationPreviewImport($warehouseId, "physical_count"))->collection(collect([
                collect(["codigo_interno" => "PHYSICAL-DUPLICATE-TEST", "saldo_contado" => "1"]),
                collect(["codigo_interno" => "PHYSICAL-DUPLICATE-TEST", "saldo_contado" => "2"]),
            ]));
            $this->fail("Un producto repetido no debe llegar a la vista previa.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Fila 3", $exception->errors()["file"][0]);

        }

        try {

            (new InventoryOperationPreviewImport($warehouseId, "physical_count"))->collection(collect([
                collect(["codigo_interno" => "PHYSICAL-DUPLICATE-TEST", "saldo_contado" => "-1"]),
            ]));
            $this->fail("Un saldo negativo no debe llegar a la vista previa.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Saldo contado", $exception->errors()["file"][0]);

        }

    }

    public function test_every_operation_has_its_own_template_and_preview_schema(): void {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");
        $product = ProductService::create([
            "internal_code" => "OPERATION-TEMPLATE-TEST",
            "name" => "Producto para operaciones",
            "price" => 10,
            "currency_id" => $currencyId,
            "status" => "active",
            "inventory" => [["warehouse_id" => $warehouseId, "initial_stock" => 4, "minimum_stock" => 0]],
        ], $userId);

        foreach(InventoryOperationTemplate::codes() as $code) {

            $definition = InventoryOperationTemplate::for($code);
            $template = new InventoryOperationTemplateExport($warehouseId, $code);
            $headings = $template->headings();

            $this->assertContains($definition["amount_heading"], $headings);
            $this->assertSame($definition["has_cost"], in_array("Costo unitario opcional", $headings, true));

            $row = [
                "codigo_interno" => "OPERATION-TEMPLATE-TEST",
                $definition["amount_key"] => $code === "physical_count" ? "0" : "2.5",
            ];

            if($definition["has_cost"]) {

                $row["costo_unitario_opcional"] = "3.2";

            }

            $preview = new InventoryOperationPreviewImport($warehouseId, $code);
            $preview->collection(collect([collect($row)]));

            $this->assertSame($product->id, $preview->items()[0]["item_id"]);
            $this->assertSame($code === "physical_count" ? "0" : "2.5", $preview->items()[0]["amount"]);
            $this->assertSame($definition["has_cost"] ? "3.2" : null, $preview->items()[0]["unit_cost"]);

        }

        $this->assertEquals(4, DB::table("warehouse_items")
            ->where("warehouse_id", $warehouseId)
            ->where("item_id", $product->id)
            ->value("quantity"));

    }

    public function test_preview_rejects_a_template_from_another_operation(): void {

        $warehouseId = (int) DB::table("warehouses")->value("id");

        $this->expectException(ValidationException::class);

        (new InventoryOperationPreviewImport($warehouseId, "manual_exit"))->collection(collect([
            collect(["codigo_interno" => "ANY", "cantidad_de_entrada" => "2"]),
        ]));

    }

    public function test_stock_listing_and_export_include_the_measurement_unit(): void {

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");

        ProductService::create([
            "internal_code" => "UNIT-STOCK-TEST",
            "name" => "Producto con unidad",
            "price" => 10,
            "currency_id" => $currencyId,
            "status" => "active",
            "inventory" => [["warehouse_id" => $warehouseId, "initial_stock" => 28, "minimum_stock" => 2]],
        ], $userId);

        $record = StockManagementService::getStockReport($warehouseId, "UNIT-STOCK-TEST")->firstOrFail();
        $export = new InventoryReportExport("stock", ["warehouse_id" => $warehouseId, "product_search" => "UNIT-STOCK-TEST"]);
        $row = $export->map($record);

        $this->assertSame("UNIDADES", $record->measurementUnit->plural_name);
        $this->assertSame("Unidad de medida", $export->headings()[3]);
        $this->assertSame("UNIDADES", $row[3]);
        $this->assertSame(28.0, $row[4]);
        $this->assertSame(2.0, $row[5]);

    }

}
