<?php

declare(strict_types=1);

namespace App\Exports\System\Customers;

use App\Services\System\Customers\Customers\{CustomerService};
use Illuminate\Database\Eloquent\{Builder};
use Maatwebsite\Excel\Concerns\{FromQuery, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class CustomerListExport extends DefaultValueBinder implements FromQuery, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles {

    public function __construct(private readonly array $filters = []) {
    }

    public function query(): Builder {

        return CustomerService::getFilteredListQuery($this->filters);

    }

    public function headings(): array {

        return ["Tipo de documento", "Número de documento", "Nombre", "Correo electrónico", "Celular", "Género", "Fecha de nacimiento", "Estado"];

    }

    public function map($customer): array {

        return [
            $customer->identityDocumentType?->name,
            $customer->document_number,
            $customer->name,
            $customer->email,
            $customer->phone_number,
            $customer->formatted_gender,
            $customer->birthdate,
            $customer->formatted_status,
        ];

    }

    public function bindValue(Cell $cell, $value): bool {

        if($cell->getRow() > 1) {

            $cell->setValueExplicit((string) ($value ?? ""), DataType::TYPE_STRING);

            return true;

        }

        return parent::bindValue($cell, $value);

    }

    public function columnWidths(): array {

        return ["A" => 23, "B" => 22, "C" => 38, "D" => 35, "E" => 20, "F" => 16, "G" => 22, "H" => 14];

    }

    public function styles(Worksheet $sheet): array {

        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:H1");
        $sheet->getStyle("A1:H1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

}
