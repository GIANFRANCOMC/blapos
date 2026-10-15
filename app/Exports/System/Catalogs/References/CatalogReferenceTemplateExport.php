<?php

declare(strict_types=1);

namespace App\Exports\System\Catalogs\References;

use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\{FromArray, ShouldAutoSize, WithHeadings, WithStyles};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class CatalogReferenceTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles {

    public function __construct(private readonly string $type) {

        if(!in_array($type, ["brands", "categories"], true)) {

            throw new InvalidArgumentException("Tipo de catálogo no válido.");

        }

    }

    public function array(): array {

        return [];

    }

    public function headings(): array {

        return $this->type === "brands"
            ? ["Nombre", "Código interno", "Descripción", "Sitio oficial", "Estado"]
            : ["Nombre", "Código interno", "Descripción", "Estado"];

    }

    public function styles(Worksheet $sheet): array {

        $lastColumn = $this->type === "brands" ? "E" : "D";

        $sheet->freezePane("A2");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

}
