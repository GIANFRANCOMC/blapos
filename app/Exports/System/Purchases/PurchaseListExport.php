<?php

declare(strict_types=1);

namespace App\Exports\System\Purchases;

use App\Helpers\System\{Numbers};
use App\Services\System\Purchases\{PurchaseService};
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithStrictNullComparison};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class PurchaseListExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithStrictNullComparison {

    public function __construct(
        private readonly array $filters = [],
        private readonly ?int $userId = null
    ) {
    }

    public function query() {

        return PurchaseService::getFilteredQuery($this->filters, $this->userId);

    }

    public function headings(): array {

        return [
            "Fecha",
            "Tipo",
            "Documento",
            "Proveedor",
            "Almacén",
            "Productos",
            "Subtotal",
            "Impuesto",
            "Total",
            "Moneda",
            "Recepción",
            "Estado",
        ];

    }

    public function map($purchase): array {

        return [
            optional($purchase->issue_date)->format("d/m/Y"),
            $purchase->formatted_document_type,
            $purchase->document_number,
            $purchase->supplier?->name,
            $purchase->warehouse?->name,
            $purchase->items->pluck("name")->implode(", "),
            Numbers::spreadsheetNumber($purchase->subtotal),
            Numbers::spreadsheetNumber($purchase->tax),
            Numbers::spreadsheetNumber($purchase->total),
            $purchase->currency?->code,
            Numbers::formatDecimal($purchase->receipt_progress)."%",
            $purchase->formatted_status,
        ];

    }

    public function styles(Worksheet $sheet): array {

        $sheet->getStyle("G:I")->getNumberFormat()->setFormatCode(Numbers::spreadsheetDecimalFormat());
        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:L1");
        $sheet->getStyle("A1:L1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A1A35"],
            ],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

}
