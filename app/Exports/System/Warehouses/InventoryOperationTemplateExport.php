<?php

declare(strict_types=1);

namespace App\Exports\System\Warehouses;

use App\Models\System\Catalogs\{Item};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Support\System\Warehouses\{InventoryOperationTemplate};
use Illuminate\Database\Eloquent\{Builder};
use Maatwebsite\Excel\Concerns\{FromQuery, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill, NumberFormat};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

class InventoryOperationTemplateExport extends DefaultValueBinder implements FromQuery, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles {

    private readonly array $operation;

    public function __construct(private readonly int $warehouseId, string $operationCode) {

        $this->operation = InventoryOperationTemplate::for($operationCode);

    }

    public function query(): Builder {

        return Item::query()
            ->where("items.type", Item::TYPE_PRODUCT)
            ->where("items.status", Item::STATUS_ACTIVE)
            ->with("measurementUnit:id,singular_name,plural_name")
            ->leftJoin("warehouse_items as warehouse_stock", function($join): void {

                $join->on("warehouse_stock.item_id", "=", "items.id")
                    ->where("warehouse_stock.warehouse_id", $this->warehouseId);

            })
            ->select(["items.id", "items.measurement_unit_id", "items.internal_code", "items.barcode", "items.name"])
            ->selectRaw("COALESCE(warehouse_stock.quantity, 0) as stock_actual")
            ->orderBy("items.name");

    }

    public function map($product): array {

        $row = [
            $product->internal_code,
            $product->barcode ?? "",
            $product->name,
            $product->measurementUnit?->plural_name ?? "UNIDADES",
            (float) $product->stock_actual,
            "",
        ];

        if($this->operation["has_cost"]) {

            $row[] = "";

        }

        return $row;

    }

    public function headings(): array {

        $headings = ["Código interno", "Código de barras", "Producto", "Unidad de medida", "Stock actual", $this->operation["amount_heading"]];

        if($this->operation["has_cost"]) {

            $headings[] = "Costo unitario opcional";

        }

        return $headings;

    }

    public function columnFormats(): array {

        $settings = CompanySettingService::group(CompanySettingService::NUMERIC_VALIDATION);
        $precision = min(8, max(0, (int) ($settings["decimal_precision"] ?? 3)));
        $quantityFormat = $precision === 0 ? "0" : "0.".str_repeat("0", $precision);

        return [
            "A" => NumberFormat::FORMAT_TEXT,
            "B" => NumberFormat::FORMAT_TEXT,
            "E" => $quantityFormat,
            "F" => $quantityFormat,
            "G" => $quantityFormat,
        ];

    }

    public function bindValue(Cell $cell, $value): bool {

        if(in_array($cell->getColumn(), ["A", "B", "C", "D"], true)) {

            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;

        }

        return parent::bindValue($cell, $value);

    }

    public function styles(Worksheet $sheet): array {

        $lastColumn = $this->operation["has_cost"] ? "G" : "F";

        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "3A507A"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        foreach(["A" => 24, "B" => 20, "C" => 48, "D" => 20, "E" => 18, "F" => 22, "G" => 24] as $column => $width) {

            $sheet->getColumnDimension($column)->setWidth($width);

        }

        return [];

    }

}
