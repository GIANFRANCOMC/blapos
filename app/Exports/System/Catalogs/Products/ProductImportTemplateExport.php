<?php

declare(strict_types=1);

namespace App\Exports\System\Catalogs\Products;

use Maatwebsite\Excel\Concerns\{FromArray, ShouldAutoSize, WithHeadings, WithStyles};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class ProductImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles {

    public function array(): array {

        return [];

    }

    public function headings(): array {

        return [
            "Nombre",
            "Precio",
            "Código interno",
            "Código de barras",
            "Descripción",
            "Stock inicial",
            "Stock mínimo",
        ];

    }

    public function styles(Worksheet $sheet): array {

        $sheet->freezePane("A2");
        $sheet->getStyle("A1:G1")->applyFromArray([
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
            ],
        ]);

        return [];

    }

}
