<?php

namespace App\Exports;

use App\Helpers\System\{Numbers};
use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnFormatting, WithColumnWidths, WithHeadings};

class ItemExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithHeadings {

    protected $data;

    public function __construct($data) {

        $this->data = $data;

    }

    public function collection() {

        return $this->data;

    }

    public function columnWidths(): array {

        return [
            "A" => 45,
            "B" => 50,
            "C" => 15,
            "D" => 15,
            "E" => 25,
        ];

    }

    public function columnFormats(): array {

        return ["C" => Numbers::spreadsheetDecimalFormat()];

    }

    public function headings(): array {

        return [
            "NOMBRE",
            "DESCRIPCIÓN",
            "PRECIO",
            "MONEDA",
            "ESTADO",
        ];

    }

}
