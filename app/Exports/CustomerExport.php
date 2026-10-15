<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithHeadings};

class CustomerExport implements FromCollection, WithColumnWidths, WithHeadings {

    protected $data;

    public function __construct($data) {

        $this->data = $data;

    }

    public function collection() {

        return $this->data;

    }

    public function columnWidths(): array {

        return [
            "A" => 30,
            "B" => 25,
            "C" => 50,
            "D" => 50,
            "E" => 25,
        ];

    }

    public function headings(): array {

        return [
            "TIPO DE DOCUMENTO",
            "NÚMERO DE DOCUMENTO",
            "NOMBRE",
            "CORREO ELECTRÓNICO",
            "ESTADO",
        ];

    }

}
