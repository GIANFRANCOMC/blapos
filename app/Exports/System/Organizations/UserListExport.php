<?php

declare(strict_types=1);

namespace App\Exports\System\Organizations;

use App\Services\System\Organizations\Users\{UserService};
use Illuminate\Database\Eloquent\{Builder};
use Maatwebsite\Excel\Concerns\{FromQuery, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class UserListExport extends DefaultValueBinder implements FromQuery, WithColumnWidths, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles {
    public function __construct(private readonly array $filters = []) {
    }

    public function query(): Builder {

        return UserService::getFilteredListQuery($this->filters);

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
            "Estado",
            "Sucursales permitidas",
            "Cajas permitidas",
            "Almacenes permitidos",
        ];

    }

    public function map($user): array {

        return [
            $user->role?->name,
            $user->identityDocumentType?->name,
            $user->document_number,
            $user->name,
            $user->email,
            $user->phone_number,
            $user->formatted_gender,
            $user->birthdate,
            $user->formatted_status,
            $user->branches->pluck("name")->implode(", ") ?: "Heredado",
            $user->cashRegisters->pluck("name")->implode(", ") ?: "Heredado",
            $user->warehouses->pluck("name")->implode(", ") ?: "Heredado",
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

        return ["A" => 25, "B" => 23, "C" => 22, "D" => 38, "E" => 34, "F" => 19, "G" => 16, "H" => 21, "I" => 16, "J" => 32, "K" => 32, "L" => 32];

    }

    public function styles(Worksheet $sheet): array {

        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:L1");
        $sheet->getStyle("A1:L1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }
}
