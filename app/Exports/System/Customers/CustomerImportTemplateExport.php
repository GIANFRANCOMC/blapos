<?php

declare(strict_types=1);

namespace App\Exports\System\Customers;

use Maatwebsite\Excel\Concerns\{FromArray, ShouldAutoSize, WithHeadings, WithStyles};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill, NumberFormat};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class CustomerImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles {

    public function array(): array {

        return [];

    }

    public function headings(): array {

        return [
            "Tipo de documento",
            "Número de documento",
            "Nombre",
            "Correo electrónico",
            "Celular",
            "Género",
            "Fecha de nacimiento",
            "Contacto de emergencia",
            "Celular de emergencia",
            "Observaciones médicas",
            "Estado",
        ];

    }

    public function styles(Worksheet $sheet): array {

        $sheet->freezePane("A2");
        $sheet->getStyle("A1:K1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle("A2:B501")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle("E2:E501")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle("I2:I501")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle("G2:G501")->getNumberFormat()->setFormatCode("yyyy-mm-dd");

        return [];

    }

}
