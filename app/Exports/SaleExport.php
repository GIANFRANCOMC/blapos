<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithHeadings};

class SaleExport implements FromCollection, WithColumnWidths, WithHeadings {

    protected $data;

    public function __construct($data) {

        $this->data = $data;

    }

    public function collection() {

        return $this->data;

    }

    public function columnWidths(): array {

        return [
            "A" => 25,
            "B" => 50,
            "C" => 20,
            "D" => 15,
            "E" => 20,
            "F" => 25,
        ];

    }

    public function headings(): array {

        return [
            "DOCUMENTO",
            "CLIENTE",
            "FECHA DE EMISIÓN",
            "TOTAL",
            "MONEDA",
            "ESTADO",
        ];

    }

}
