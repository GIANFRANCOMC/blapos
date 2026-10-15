<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithHeadings};

class ItemExport implements FromCollection, WithColumnWidths, WithHeadings {

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
