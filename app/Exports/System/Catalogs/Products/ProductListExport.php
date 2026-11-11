<?php

declare(strict_types=1);

namespace App\Exports\System\Catalogs\Products;

use App\Helpers\System\{Numbers, StockStatus};
use App\Models\System\Catalogs\{Item};
use App\Services\System\Catalogs\Products\{ProductService};
use Illuminate\Database\Eloquent\{Builder};
use Maatwebsite\Excel\Concerns\{FromQuery, WithColumnWidths, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles, WithStrictNullComparison};
use Maatwebsite\Excel\Events\{AfterSheet};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class ProductListExport extends DefaultValueBinder implements FromQuery, WithColumnWidths, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles, WithStrictNullComparison {

    private const LAST_COLUMN = "R";

    private array $alertRows = [];

    private array $healthyRows = [];

    private int $currentRow = 1;

    public function __construct(
        private readonly array $filters = []
    ) {
    }

    public function query(): Builder {

        return ProductService::getFilteredListQuery($this->filters);

    }

    public function headings(): array {

        return [
            "Código interno",
            "Código de barras",
            "Producto",
            "Marca",
            "Categorías",
            "Descripción comercial",
            "Moneda",
            "Precio de venta",
            "Precio mínimo",
            "Precio máximo",
            "Stock total",
            "Almacenes",
            "Almacenes con alerta",
            "Estado del stock",
            "Detalle por almacén",
            "Visible para clientes",
            "Precio visible",
            "Estado",
        ];

    }

    public function map($item): array {

        /** @var Item $item */
        $this->currentRow++;

        $warehouseItems = $item->warehouseItems;
        $alertCount = $warehouseItems
            ->filter(fn($warehouseItem) => StockStatus::fromLevels($warehouseItem->quantity, $warehouseItem->minimum_stock) !== StockStatus::Healthy)
            ->count();

        $status = StockStatus::forProduct($item->total_stock_quantity, $alertCount > 0);

        if($status !== StockStatus::Healthy) {

            $this->alertRows[] = $this->currentRow;

        }else {

            $this->healthyRows[] = $this->currentRow;

        }

        $categories = $item->categoryItems
            ->pluck("category.name")
            ->filter()
            ->unique()
            ->sort()
            ->implode(", ");

        $inventoryDetail = $warehouseItems
            ->map(function($warehouseItem) {

                $branchName = $warehouseItem->warehouse?->branch?->name;
                $warehouseName = $warehouseItem->warehouse?->name ?? "Almacén";
                $location = $branchName ? "{$branchName} / {$warehouseName}" : $warehouseName;
                $quantity = Numbers::formatDecimal($warehouseItem->quantity);
                $minimum = Numbers::formatDecimal($warehouseItem->minimum_stock);
                $status = StockStatus::fromLevels($warehouseItem->quantity, $warehouseItem->minimum_stock)->label();

                return "{$location}: {$quantity} (mín. {$minimum}) · {$status}";

            })
            ->implode(" | ");

        return [
            $item->internal_code,
            $item->barcode,
            $item->name,
            $item->brand?->name,
            $categories,
            $item->description,
            $item->currency?->sign ?? $item->currency?->code,
            Numbers::spreadsheetNumber($item->price),
            $item->min_price !== null ? Numbers::spreadsheetNumber($item->min_price) : null,
            $item->max_price !== null ? Numbers::spreadsheetNumber($item->max_price) : null,
            Numbers::spreadsheetNumber($item->total_stock_quantity),
            $warehouseItems->count(),
            $alertCount,
            $status->label(),
            $inventoryDetail,
            $item->see_my_web ? "Sí" : "No",
            $item->see_my_web && $item->see_my_web_price ? "Sí" : "No",
            $item->formatted_status,
        ];

    }

    public function columnWidths(): array {

        return [
            "A" => 20,
            "B" => 20,
            "C" => 30,
            "D" => 22,
            "E" => 30,
            "F" => 40,
            "G" => 12,
            "H" => 16,
            "I" => 16,
            "J" => 16,
            "K" => 14,
            "L" => 12,
            "M" => 23,
            "N" => 24,
            "O" => 55,
            "P" => 20,
            "Q" => 17,
            "R" => 14,
        ];

    }

    public function bindValue(Cell $cell, $value): bool {

        $textColumns = ["A", "B", "C", "D", "E", "F", "G", "N", "O", "P", "Q", "R"];

        if($cell->getRow() > 1 && (in_array($cell->getColumn(), $textColumns, true)
            || ($cell->getColumn() === "K" && is_string($value)))) {

            $cell->setValueExplicit((string) ($value ?? ""), DataType::TYPE_STRING);

            return true;

        }

        return parent::bindValue($cell, $value);

    }

    public function styles(Worksheet $sheet): array {

        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:".self::LAST_COLUMN."1");
        $sheet->getStyle("A1:".self::LAST_COLUMN."1")->applyFromArray([
            "font" => [
                "bold" => true,
                "color" => ["rgb" => "FFFFFF"],
            ],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A1A35"],
            ],
            "alignment" => [
                "horizontal" => Alignment::HORIZONTAL_CENTER,
                "vertical" => Alignment::VERTICAL_CENTER,
            ],
            "borders" => [
                "bottom" => [
                    "borderStyle" => Border::BORDER_MEDIUM,
                    "color" => ["rgb" => "2899E5"],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getStyle("H:K")->getNumberFormat()->setFormatCode(Numbers::spreadsheetDecimalFormat());
        $sheet->getStyle("A:".self::LAST_COLUMN)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("E:F")->getAlignment()->setWrapText(true);
        $sheet->getStyle("O:O")->getAlignment()->setWrapText(true);

        return [];

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class => function(AfterSheet $event) {

                foreach($this->alertRows as $row) {

                    $event->sheet->getStyle("M{$row}:N{$row}")->applyFromArray([
                        "font" => ["bold" => true, "color" => ["rgb" => "991B1B"]],
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "FEE2E2"],
                        ],
                    ]);

                }

                foreach($this->healthyRows as $row) {

                    $event->sheet->getStyle("N{$row}")->applyFromArray([
                        "font" => ["color" => ["rgb" => "047857"]],
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "D1FAE5"],
                        ],
                    ]);

                }

            },
        ];

    }

}
