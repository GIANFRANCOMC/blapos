<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\{FromCollection, WithColumnWidths, WithHeadings};

class BranchExport implements FromCollection, WithColumnWidths, WithHeadings {

    protected $data;

    public function __construct($data) {

        $this->data = $data;

    }

    public function collection() {

        return $this->data;

    }

    public function columnWidths(): array {

        return [
            "A" => 50,
            "B" => 18,
            "C" => 25,
        ];

    }

    public function headings(): array {

        return [
            "NOMBRE",
            "AFORO",
            "ESTADO",
        ];

    }

}
