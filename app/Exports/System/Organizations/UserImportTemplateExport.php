<?php

declare(strict_types=1);

namespace App\Exports\System\Organizations;

use Maatwebsite\Excel\Concerns\{FromArray, ShouldAutoSize, WithHeadings, WithStyles};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill, NumberFormat};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class UserImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles {
    public function array(): array {

        return [];

    }

    public function headings(): array {

        return [
            "Perfil",
            "Tipo de documento",
            "Número de documento",
            "Nombre",
            "Correo electrónico",
            "Celular",
            "Género",
            "Fecha de nacimiento",
        ];

    }

    public function styles(Worksheet $sheet): array {

        $sheet->freezePane("A2");
        $sheet->getStyle("A1:H1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("C2:C501")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle("F2:F501")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle("H2:H501")->getNumberFormat()->setFormatCode("yyyy-mm-dd");

        return [];

    }
}
