<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\System\Warehouses\StockAlertsExport;
use App\Helpers\System\{Numbers};
use App\Services\System\Warehouses\StockManagement\StockAlertsPdfRenderer;
use App\Services\System\Warehouses\StockManagement\StockManagementService;
use Illuminate\Support\LazyCollection;
use Tests\TestCase;

final class StockAlertsExportTest extends TestCase {

    public function test_report_query_uses_current_stock_scoped_to_one_warehouse(): void {

        $query = StockManagementService::stockAlertsReportQuery(7);
        $this->assertSame([7, "active", 7, "active", "open", "product", 0], $query->getBindings());
        $this->assertStringContainsString(Numbers::sqlRound("SUM(quantity)"), $query->toSql());
        $this->assertStringContainsString("`warehouse_id` = ? and `status` = ?", $query->toSql());
        $this->assertStringContainsString("COALESCE(stock.quantity, 0) <= 0", $query->toSql());
        $this->assertStringContainsString("`alerts`.`status` = ?", $query->toSql());

        $filtered = StockManagementService::stockAlertsReportQuery(7, "tuerca");
        $this->assertSame([7, "active", 7, "active", "open", "product", 0, "%tuerca%", "%tuerca%", "%tuerca%"], $filtered->getBindings());
        $this->assertStringContainsString("`products`.`barcode` like ?", $filtered->toSql());

        $modalQuery = StockManagementService::stockAlertsQuery(7, "open", "tuerca");
        $this->assertSame($filtered->getBindings(), $modalQuery->getBindings());
        $this->assertSame($filtered->toSql(), $modalQuery->toSql());

    }

    public function test_minimum_filter_matches_the_modal_and_both_exports(): void {

        foreach(["configured" => ">", "unconfigured" => "<="] as $status => $operator) {

            $modal = StockManagementService::stockAlertsQuery(7, "open", "tuerca", $status);
            $report = StockManagementService::stockAlertsReportQuery(7, "tuerca", $status);
            $excel = (new StockAlertsExport(7, "tuerca", $status))->query();

            $this->assertStringContainsString("COALESCE(stock.minimum_stock, 0) {$operator} ?", $modal->toSql());
            $this->assertStringContainsString("COALESCE(stock.minimum_stock, 0) {$operator} ?", $report->toSql());
            $this->assertSame([7, "active", 7, "active", "open", "product", 0, "%tuerca%", "%tuerca%", "%tuerca%", 0], $modal->getBindings());
            $this->assertSame($modal->getBindings(), $report->getBindings());
            $this->assertSame($report->toSql(), $excel->toSql());
            $this->assertSame($report->getBindings(), $excel->getBindings());

        }

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

        $row->detected_at = null;
        $this->assertSame("—", (new StockAlertsExport(1))->map($row)[7]);

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

            yield (object) [
                "product_name" => "Producto agotado sin mínimo",
                "internal_code" => "PROD-002",
                "barcode" => null,
                "quantity" => 0,
                "minimum_stock" => 0,
                "unit_abbreviation" => "UND",
                "detected_at" => null,
            ];

        });

        $warehouse = (object) ["name" => "Almacén 1", "branch" => (object) ["name" => "Sede Principal"]];
        $pdf = (new StockAlertsPdfRenderer())->render($warehouse, null, $rows, 2, now());

        $this->assertStringStartsWith("%PDF", $pdf);
        $this->assertStringContainsString("%%EOF", $pdf);

    }

}
