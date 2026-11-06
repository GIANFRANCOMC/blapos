<?php

declare(strict_types=1);

namespace App\Exports\System\Warehouses;

use App\Services\System\Warehouses\StockManagement\StockManagementService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\{FromQuery, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill, NumberFormat};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class StockAlertsExport extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles {

    public function __construct(
        private readonly int $warehouseId,
        private readonly string $productSearch = "",
        private readonly string $minimumStatus = ""
    ) {
    }

    public function query(): Builder {

        return StockManagementService::stockAlertsReportQuery($this->warehouseId, $this->productSearch, $this->minimumStatus);

    }

    public function headings(): array {

        return ["Producto", "Código interno", "Código de barras", "Stock actual", "Stock mínimo", "Faltante", "Unidad", "Detectada", "Estado"];

    }

    public function map($alert): array {

        $quantity = (float) $alert->quantity;
        $minimum = (float) $alert->minimum_stock;

        return [
            $alert->product_name,
            $alert->internal_code,
            $alert->barcode ?? "",
            $quantity,
            $minimum,
            max(0, $minimum - $quantity),
            $alert->unit_abbreviation,
            $alert->detected_at ? Carbon::parse($alert->detected_at)->format("d/m/Y H:i") : "—",
            "Abierta",
        ];

    }

    public function bindValue(Cell $cell, $value): bool {

        if($cell->getRow() > 1 && in_array($cell->getColumn(), ["B", "C"], true)) {

            $cell->setValueExplicit((string) ($value ?? ""), DataType::TYPE_STRING);

            return true;

        }

        return parent::bindValue($cell, $value);

    }

    public function columnFormats(): array {

        return [
            "B" => NumberFormat::FORMAT_TEXT,
            "C" => NumberFormat::FORMAT_TEXT,
            "D" => "#,##0.000",
            "E" => "#,##0.000",
            "F" => "#,##0.000",
        ];

    }

    public function columnWidths(): array {

        return ["A" => 48, "B" => 19, "C" => 23, "D" => 17, "E" => 17, "F" => 17, "G" => 13, "H" => 21, "I" => 14];

    }

    public function styles(Worksheet $sheet): array {

        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:I1");
        $sheet->getStyle("A1:I1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "3A507A"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

}
