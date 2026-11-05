<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\System\Warehouses\StockAlertsExport;
use App\Services\System\Warehouses\StockManagement\StockAlertsPdfRenderer;
use App\Services\System\Warehouses\StockManagement\StockManagementService;
use Illuminate\Support\LazyCollection;
use Tests\TestCase;

final class StockAlertsExportTest extends TestCase {

    public function test_report_query_is_scoped_to_one_warehouse_and_open_alerts(): void {

        $query = StockManagementService::stockAlertsReportQuery(7);

        $this->assertSame([7, "open"], $query->getBindings());
        $this->assertStringContainsString("`stock`.`warehouse_id` = ?", $query->toSql());
        $this->assertStringContainsString("`alerts`.`status` = ?", $query->toSql());

        $filtered = StockManagementService::stockAlertsReportQuery(7, "tuerca");
        $this->assertSame([7, "open", "%tuerca%", "%tuerca%", "%tuerca%"], $filtered->getBindings());
        $this->assertStringContainsString("`products`.`barcode` like ?", $filtered->toSql());

        $modalQuery = StockManagementService::stockAlertsQuery(7, "open", "tuerca");
        $this->assertSame([7, "open", "%tuerca%", "%tuerca%", "%tuerca%"], $modalQuery->getBindings());

    }

    public function test_excel_mapping_preserves_codes_and_quantities(): void {

        $row = (object) [
            "product_name" => "Producto de prueba",
            "internal_code" => "PROD-001",
            "barcode" => "0000123456",
            "quantity" => "2.500",
            "minimum_stock" => "12.000",
            "unit_abbreviation" => "UND",
            "detected_at" => "2026-11-05 10:00:00",
        ];

        $this->assertSame([
            "Producto de prueba",
            "PROD-001",
            "0000123456",
            2.5,
            12.0,
            9.5,
            "UND",
            "05/11/2026 10:00",
            "Abierta",
        ], (new StockAlertsExport(1))->map($row));

    }

    public function test_pdf_renders_flat_lazy_rows_without_database_access(): void {

        $rows = LazyCollection::make(static function() {

            yield (object) [
                "product_name" => "Producto de prueba",
                "internal_code" => "PROD-001",
                "barcode" => "0000123456",
                "quantity" => "2.500",
                "minimum_stock" => "12.000",
                "unit_abbreviation" => "UND",
                "detected_at" => "2026-11-05 10:00:00",
            ];

        });

        $warehouse = (object) ["name" => "Almacén 1", "branch" => (object) ["name" => "Sede Principal"]];
        $pdf = (new StockAlertsPdfRenderer())->render($warehouse, null, $rows, 1, now());

        $this->assertStringStartsWith("%PDF", $pdf);
        $this->assertStringContainsString("%%EOF", $pdf);

    }

}
