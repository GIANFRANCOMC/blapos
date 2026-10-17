<?php

declare(strict_types=1);

namespace App\Exports\System\Catalogs\Offerings;

use App\Models\System\Catalogs\{Item};
use App\Services\System\Catalogs\Services\{ServiceService};
use App\Services\System\Catalogs\Subscriptions\{SubscriptionService};
use Illuminate\Database\Eloquent\{Builder};
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DataType, DefaultValueBinder};
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Fill};
use PhpOffice\PhpSpreadsheet\Worksheet\{Worksheet};

final class OfferingListExport extends DefaultValueBinder implements FromQuery, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles {

    public function __construct(
        private readonly string $type,
        private readonly array $filters = []
    ) {

        if(!in_array($type, [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION], true)) {

            throw new \InvalidArgumentException("Tipo de oferta no compatible.");

        }

    }

    public function query(): Builder {

        return $this->type === Item::TYPE_SERVICE
            ? ServiceService::getFilteredListQuery($this->filters, true)
            : SubscriptionService::getFilteredListQuery($this->filters, true);

    }

    public function headings(): array {

        $common = [
            "Código interno", "Nombre", "Descripción comercial", "Categorías", "Moneda",
            "Precio de venta", "Precio mínimo", "Precio máximo"
        ];

        $specific = $this->type === Item::TYPE_SERVICE
            ? ["Duración estimada (minutos)"]
            : ["Duración (valor)", "Duración (tipo)", "Asistencias por día", "Beneficios", "Restricciones"];

        return array_merge($common, $specific, [
            "Controlar cupos", "Cupos totales", "Cupos disponibles", "Disponible hasta",
            "Tipo de comisión", "Valor de comisión", "IGV exonerado", "Precio incluye IGV",
            "Publicado", "Mostrar precio", "Estado"
        ]);

    }

    public function map($item): array {

        /** @var Item $item */
        $categories = $item->categoryItems->pluck("category.name")->filter()->unique()->sort()->implode(", ");

        $common = [
            $item->internal_code,
            $item->name,
            $item->description,
            $categories,
            $item->currency?->sign ?? $item->currency?->code,
            (float) $item->price,
            $item->min_price !== null ? (float) $item->min_price : null,
            $item->max_price !== null ? (float) $item->max_price : null,
        ];

        $specific = $this->type === Item::TYPE_SERVICE
            ? [$item->estimated_duration_minutes]
            : [
                $item->duration_value,
                $item->duration_type,
                $item->attendance_limit_per_day,
                implode(", ", $item->benefits ?? []),
                implode(", ", $item->restrictions ?? []),
            ];

        return array_merge($common, $specific, [
            $this->yesNo($item->capacity_control_enabled),
            $item->capacity_control_enabled ? $item->capacity_limit : null,
            $item->capacity_control_enabled ? $item->available_capacity : null,
            $item->expires_at?->format("Y-m-d"),
            $item->commission_type ?? "none",
            $item->commission_value !== null ? (float) $item->commission_value : null,
            $this->yesNo($item->igv_exempt),
            $this->yesNo($item->price_includes_tax),
            $this->yesNo((bool) $item->see_my_web),
            $this->yesNo((bool) ($item->see_my_web && $item->see_my_web_price)),
            $item->formatted_status,
        ]);

    }

    public function bindValue(Cell $cell, $value): bool {

        if(is_string($value) && preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)) {

            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;

        }

        return parent::bindValue($cell, $value);

    }

    public function styles(Worksheet $sheet): array {

        $lastColumn = $sheet->getHighestColumn();

        $sheet->freezePane("A2");
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
            "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "1A1A35"]],
            "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];

    }

    private function yesNo(bool $value): string {

        return $value ? "Sí" : "No";

    }

}
