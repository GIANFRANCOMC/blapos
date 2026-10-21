<?php

declare(strict_types=1);

namespace App\Exports\System\Warehouses;

use App\Services\System\Warehouses\Inventory\{InventoryGuideService};
use App\Services\System\Warehouses\StockManagement\{StockManagementService};
use Illuminate\Support\{Collection};
use Maatwebsite\Excel\Concerns\{FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles};
use Maatwebsite\Excel\Events\{AfterSheet};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class InventoryReportExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithStyles {

    private int $currentRow = 1;

    private array $attentionRows = [];

    public function __construct(
        private readonly string $view,
        private readonly array $filters
    ) {
    }

    public function collection(): Collection {

        if($this->view === "stock") {

            if(($this->filters["warehouse_id"] ?? null) === "all") {

                return StockManagementService::getConsolidatedStock(
                    (string) ($this->filters["product_search"] ?? ""),
                    $this->filters["allowed_warehouse_ids"] ?? null
                );

            }

            return StockManagementService::getStockReport(
                (int) ($this->filters["warehouse_id"] ?? 0),
                (string) ($this->filters["product_search"] ?? "")
            );

        }

        if($this->view === "guides") {

            $filters = $this->filters;

            if(($filters["warehouse_id"] ?? null) === "all") {

                unset($filters["warehouse_id"]);

            }

            return InventoryGuideService::query($filters)->get();

        }

        $filters = $this->filters;

        if(($filters["warehouse_id"] ?? null) === "all") {

            unset($filters["warehouse_id"]);

        }

        if($this->view === "transfers") {

            $filters["origin_types"] = ["transfer_in", "transfer_out"];

        }

        return StockManagementService::getKardexReport($filters);

    }

    public function headings(): array {

        if($this->view === "stock") {

            return [
                "Código interno",
                "Código de barras",
                "Producto",
                "Unidad de medida",
                "Stock actual",
                "Stock mínimo",
                "Almacenes",
                "Almacenes con alerta",
                "Situación",
            ];

        }

        if($this->view === "valued") {

            return [
                "Fecha",
                "Almacén",
                "Código interno",
                "Código de barras",
                "Producto",
                "Unidad de medida",
                "Movimiento",
                "Origen",
                "Variación",
                "Costo unitario",
                "Valor anterior",
                "Valor del movimiento",
                "Valor resultante",
                "Motivo",
                "Referencia",
                "Responsable",
            ];

        }

        if($this->view === "guides") {

            return [
                "Número",
                "Fecha",
                "Almacén",
                "Sucursal",
                "Tipo",
                "Productos",
                "Referencia",
                "Motivo",
                "Estado",
                "Responsable",
            ];

        }

        return [
            "Fecha",
            "Almacén",
            "Código interno",
            "Código de barras",
            "Producto",
            "Unidad de medida",
            "Movimiento",
            "Origen",
            "Saldo anterior",
            "Variación",
            "Saldo resultante",
            "Motivo",
            "Referencia",
            "Responsable",
        ];

    }

    public function map($record): array {

        $this->currentRow++;

        if($this->view === "stock") {

            $stock = (float) ($record->stock_quantity ?? 0);
            $minimum = (float) ($record->minimum_stock ?? 0);
            $status = $stock <= 0
                ? "Sin existencias"
                : ($stock <= $minimum ? "Stock bajo" : "Stock saludable");

            if($stock <= $minimum) {

                $this->attentionRows[] = $this->currentRow;

            }

            return [
                $record->internal_code,
                $record->barcode,
                $record->name,
                $record->measurementUnit?->plural_name ?? "UNIDADES",
                $stock,
                $minimum,
                $this->warehouseBreakdownText($record->warehouse_breakdown ?? [], $record->measurementUnit),
                (int) ($record->alert_warehouses_count ?? 0),
                $status,
            ];

        }

        if($this->view === "valued") {

            return [
                optional($record->created_at)->format("d/m/Y H:i"),
                $record->warehouse?->name,
                $record->item?->internal_code,
                $record->item?->barcode,
                $record->item?->name,
                $record->item?->measurementUnit?->plural_name ?? "UNIDADES",
                $this->movementLabel((string) $record->movement_type),
                $this->originLabel((string) $record->origin_type),
                (float) $record->quantity_change,
                (float) $record->unit_cost,
                (float) $record->value_before,
                (float) $record->value_change,
                (float) $record->value_after,
                $record->reason,
                $record->reference,
                $record->user?->name ?? "Proceso del sistema",
            ];

        }

        if($this->view === "guides") {

            return [
                $record->number,
                optional($record->issue_date)->format("d/m/Y"),
                $record->warehouse?->name,
                $record->warehouse?->branch?->name,
                $record->guide_type === "entry" ? "Entrada" : "Salida",
                $record->items->map(fn($item) => $item->item?->name." x ".(float) $item->quantity." ".mb_strtolower((string) ($item->item?->measurementUnit?->plural_name ?? "UNIDADES")))->implode("; "),
                $record->reference,
                $record->reason,
                $record->status === "confirmed" ? "Confirmada" : $record->status,
                $record->confirmedBy?->name,
            ];

        }

        return [
            optional($record->created_at)->format("d/m/Y H:i"),
            $record->warehouse?->name,
            $record->item?->internal_code,
            $record->item?->barcode,
            $record->item?->name,
            $record->item?->measurementUnit?->plural_name ?? "UNIDADES",
            $this->movementLabel((string) $record->movement_type),
            $this->originLabel((string) $record->origin_type),
            (float) $record->quantity_before,
            (float) $record->quantity_change,
            (float) $record->quantity_after,
            $record->reason,
            $record->reference,
            $record->user?->name ?? "Proceso del sistema",
        ];

    }

    public function styles(Worksheet $sheet): array {

        $lastColumn = match ($this->view) {
            "stock" => "I",
            "valued" => "P",
            "guides" => "J",
            default => "N"
        };

        $sheet->freezePane("A2");
        $sheet->setAutoFilter("A1:{$lastColumn}1");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => [
                "fillType" => Fill::FILL_SOLID,
                "startColor" => ["rgb" => "1A1A35"],
            ],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

    public function bindValue(Cell $cell, $value): bool {

        $textColumns = match ($this->view) {
            "stock" => ["A", "B", "C", "D", "G", "I"],
            "valued" => ["A", "B", "C", "D", "E", "F", "G", "H", "N", "O", "P"],
            "guides" => ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J"],
            default => ["A", "B", "C", "D", "E", "F", "G", "H", "L", "M", "N"]
        };

        if($cell->getRow() > 1 && in_array($cell->getColumn(), $textColumns, true)) {

            $cell->setValueExplicit((string) ($value ?? ""), DataType::TYPE_STRING);

            return true;

        }

        return parent::bindValue($cell, $value);

    }

    public function registerEvents(): array {

        return [
            AfterSheet::class => function(AfterSheet $event) {

                foreach($this->attentionRows as $row) {

                    $event->sheet->getStyle("E{$row}:I{$row}")->applyFromArray([
                        "font" => ["color" => ["rgb" => "92400E"]],
                        "fill" => [
                            "fillType" => Fill::FILL_SOLID,
                            "startColor" => ["rgb" => "FEF3C7"],
                        ],
                    ]);

                }

            },
        ];

    }

    private function movementLabel(string $type): string {

        return [
            "entry" => "Entrada",
            "exit" => "Salida",
            "correction" => "Corrección",
        ][$type] ?? $type;

    }

    private function originLabel(string $origin): string {

        return [
            "product_opening" => "Creación de producto",
            "manual" => "Operación manual",
            "sale" => "Venta",
            "sale_cancellation" => "Devolución automática por anulación",
            "purchase" => "Compra",
            "purchase_cancellation" => "Anulación de compra",
            "transfer_out" => "Traslado enviado",
            "transfer_in" => "Traslado recibido",
            "customer_return" => "Devolución de cliente",
            "supplier_return" => "Devolución a proveedor",
            "physical_count" => "Toma física",
        ][$origin] ?? $origin;

    }

    private function warehouseBreakdownText($breakdown, $unit): string {

        return collect($breakdown)
            ->map(function($warehouse) use ($unit) {

                $branchName = $warehouse["branch_name"] ?? "";
                $warehouseName = $warehouse["warehouse_name"] ?? "Almacén";
                $location = trim(($branchName !== "" ? $branchName." / " : "").$warehouseName);

                $quantity = (float) ($warehouse["quantity"] ?? 0);
                $label = abs($quantity) === 1
                    ? ($unit?->singular_name ?? "UNIDAD")
                    : ($unit?->plural_name ?? "UNIDADES");

                return $location.": ".$quantity." ".mb_strtolower($label);

            })
            ->implode("; ");

    }

}
