<?php

declare(strict_types=1);

namespace App\Imports\System\Warehouses;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Warehouses\{WarehouseItem};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use Illuminate\Support\{Collection};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToCollection, WithHeadingRow};

final class PhysicalCountPreviewImport implements SkipsEmptyRows, ToCollection, WithHeadingRow {

    private const MAX_ITEMS = 100;

    private array $items = [];

    public function __construct(private readonly int $warehouseId) {
    }

    public function collection(Collection $rows): void {

        if($this->items !== []) {

            throw ValidationException::withMessages(["file" => ["La toma física debe estar en una sola hoja."]]);

        }

        if($rows->isEmpty()) {

            throw ValidationException::withMessages(["file" => ["La plantilla no contiene productos."]]);

        }

        $headers = array_keys($rows->first()->toArray());

        if(!in_array("codigo_interno", $headers, true) || !in_array("saldo_contado", $headers, true)) {

            throw ValidationException::withMessages(["file" => ["La plantilla debe incluir Código interno y Saldo contado."]]);

        }

        $countedRows = $rows->filter(fn(Collection $row): bool => $this->hasValue($row->get("saldo_contado")));

        if($countedRows->isEmpty()) {

            throw ValidationException::withMessages(["file" => ["Completa el Saldo contado de al menos un producto."]]);

        }

        if($countedRows->count() > self::MAX_ITEMS) {

            throw ValidationException::withMessages(["file" => ["Puedes revisar hasta 100 productos por operación. Divide el archivo en varias tomas físicas."]]);

        }

        $codes = $countedRows
            ->map(fn(Collection $row): string => trim((string) $row->get("codigo_interno", "")))
            ->filter()
            ->values()
            ->all();

        $products = Item::query()
            ->where("type", Item::TYPE_PRODUCT)
            ->where("status", Item::STATUS_ACTIVE)
            ->whereIn("internal_code", $codes)
            ->get(["id", "internal_code", "barcode", "name"])
            ->keyBy(fn(Item $product): string => mb_strtolower($product->internal_code));

        $currentStock = WarehouseItem::query()
            ->where("warehouse_id", $this->warehouseId)
            ->whereIn("item_id", $products->pluck("id"))
            ->pluck("quantity", "item_id");

        $settings = CompanySettingService::group(CompanySettingService::NUMERIC_VALIDATION);
        $precision = min(8, max(0, (int) ($settings["decimal_precision"] ?? 3)));
        $maxValue = (float) ($settings["default_max_value"] ?? 999999999999.999);
        $seen = [];

        foreach($countedRows as $index => $row) {

            $rowNumber = $index + 2;
            $code = trim((string) $row->get("codigo_interno", ""));
            $product = $products->get(mb_strtolower($code));

            if(!$product) {

                throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: el código interno no corresponde a un producto activo."]]);

            }

            $barcode = trim((string) $row->get("codigo_de_barras", ""));

            if($barcode !== "" && $barcode !== (string) ($product->barcode ?? "")) {

                throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: el código de barras no coincide con el producto."]]);

            }

            if(isset($seen[$product->id])) {

                throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: el producto está repetido en la toma física."]]);

            }

            $seen[$product->id] = true;
            $balance = $this->balance($row->get("saldo_contado"), $rowNumber, $precision, $maxValue);

            $this->items[] = [
                "item_id" => (int) $product->id,
                "internal_code" => $product->internal_code,
                "name" => $product->name,
                "current_stock" => (string) ($currentStock->get($product->id) ?? "0"),
                "resulting_balance" => $balance,
            ];

        }

    }

    public function items(): array {

        return $this->items;

    }

    private function hasValue(mixed $value): bool {

        return $value !== null && trim((string) $value) !== "";

    }

    private function balance(mixed $value, int $rowNumber, int $precision, float $maxValue): string {

        $balance = str_replace(",", ".", trim((string) $value));
        $pattern = $precision === 0
            ? '/^\d+$/D'
            : '/^\d+(?:\.\d{1,'.$precision.'})?$/D';

        if(!preg_match($pattern, $balance) || (float) $balance > $maxValue) {

            throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: el Saldo contado debe ser un número no negativo con hasta {$precision} decimales."]]);

        }

        return $balance;

    }

}
