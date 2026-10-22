<?php

declare(strict_types=1);

namespace App\Exports\System\Catalogs\References;

use App\Services\System\Catalogs\Brands\{BrandService};
use App\Services\System\Catalogs\Categories\{CategoryService};
use Illuminate\Database\Eloquent\{Builder};
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class CatalogReferenceListExport extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles {

    public function __construct(
        private readonly string $type,
        private readonly array $filters = []
    ) {

        if(!in_array($type, ["brands", "categories"], true)) {

            throw new InvalidArgumentException("Tipo de catálogo no válido.");

        }

    }

    public function query(): Builder {

        return $this->type === "brands"
            ? BrandService::getFilteredListQuery($this->filters)
            : CategoryService::getFilteredListQuery($this->filters);

    }

    public function headings(): array {

        return $this->type === "brands"
            ? ["Código interno", "Marca", "Descripción", "Sitio oficial", "Productos activos", "Estado"]
            : ["Código interno", "Categoría", "Descripción", "Ítems activos", "Estado"];

    }

    public function map($record): array {

        if($this->type === "brands") {

            return [
                $record->internal_code,
                $record->name,
                $record->description,
                $record->website_url,
                (int) $record->active_items_count,
                $record->formatted_status,
            ];

        }

        return [
            $record->internal_code,
            $record->name,
            $record->description,
            (int) $record->active_items_count,
            $record->formatted_status,
        ];

    }

    public function bindValue(Cell $cell, $value): bool {

        $numericColumns = $this->type === "brands" ? ["E"] : ["D"];

        if($cell->getRow() > 1 && !in_array($cell->getColumn(), $numericColumns, true)) {

            $cell->setValueExplicit((string) ($value ?? ""), DataType::TYPE_STRING);

            return true;

        }

        return parent::bindValue($cell, $value);

    }

    public function styles(Worksheet $sheet): array {

        $lastColumn = $this->type === "brands" ? "F" : "E";

        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

}
