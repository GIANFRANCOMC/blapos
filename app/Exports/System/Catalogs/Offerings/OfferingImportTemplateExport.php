<?php

declare(strict_types=1);

namespace App\Exports\System\Catalogs\Offerings;

use App\Models\System\Catalogs\{Item};
use Maatwebsite\Excel\Concerns\{FromArray, ShouldAutoSize, WithHeadings, WithStyles};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class OfferingImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles {

    public function __construct(private readonly string $type) {

        if(!in_array($type, [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION], true)) {

            throw new \InvalidArgumentException("Tipo de oferta no compatible.");

        }

    }

    public function array(): array {

        return [];

    }

    public function headings(): array {

        $common = ["Nombre", "Precio", "Código interno", "Descripción"];

        return $this->type === Item::TYPE_SERVICE
            ? array_merge($common, ["Duración estimada minutos"])
            : array_merge($common, ["Duración valor", "Duración tipo"]);

    }

    public function styles(Worksheet $sheet): array {

        $lastColumn = $this->type === Item::TYPE_SERVICE ? "E" : "F";

        $sheet->freezePane("A2");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

}
