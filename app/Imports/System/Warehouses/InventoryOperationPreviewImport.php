<?php

declare(strict_types=1);

namespace App\Imports\System\Warehouses;

use App\Models\System\Catalogs\{Item};
use App\Models\System\Warehouses\{WarehouseItem};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Support\System\Warehouses\{InventoryOperationTemplate};
use Illuminate\Support\{Collection};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToCollection, WithHeadingRow};

class InventoryOperationPreviewImport implements SkipsEmptyRows, ToCollection, WithHeadingRow {

    private const MAX_ITEMS = 100;

    private array $items = [];

    private readonly array $operation;

    public function __construct(private readonly int $warehouseId, string $operationCode) {

        $this->operation = InventoryOperationTemplate::for($operationCode);

    }

    public function collection(Collection $rows): void {

        if($this->items !== []) {

            throw ValidationException::withMessages(["file" => ["La plantilla debe estar en una sola hoja."]]);

        }

        if($rows->isEmpty()) {

            throw ValidationException::withMessages(["file" => ["La plantilla no contiene productos."]]);

        }

        $amountKey = $this->operation["amount_key"];
        $headers = array_keys($rows->first()->toArray());

        if(!in_array("codigo_interno", $headers, true) || !in_array($amountKey, $headers, true)) {

            throw ValidationException::withMessages(["file" => ["Esta plantilla debe incluir Código interno y {$this->operation['amount_heading']}. Descarga la plantilla del tipo de operación seleccionado."]]);

        }

        $enteredRows = $rows->filter(fn(Collection $row): bool => $this->hasValue($row->get($amountKey)));

        if($enteredRows->isEmpty()) {

            throw ValidationException::withMessages(["file" => ["Completa {$this->operation['amount_heading']} de al menos un producto."]]);

        }

        if($enteredRows->count() > self::MAX_ITEMS) {

            throw ValidationException::withMessages(["file" => ["Puedes revisar hasta 100 productos por operación. Divide el archivo en varios lotes."]]);

        }

        $codes = $enteredRows
            ->map(fn(Collection $row): string => trim((string) $row->get("codigo_interno", "")))
            ->filter()
            ->values()
            ->all();

        $products = Item::query()
            ->where("type", Item::TYPE_PRODUCT)
            ->where("status", Item::STATUS_ACTIVE)
            ->whereIn("internal_code", $codes)
            ->with("measurementUnit:id,singular_name,plural_name")
            ->get(["id", "measurement_unit_id", "internal_code", "barcode", "name"])
            ->keyBy(fn(Item $product): string => mb_strtolower($product->internal_code));

        $currentStock = WarehouseItem::query()
            ->where("warehouse_id", $this->warehouseId)
            ->whereIn("item_id", $products->pluck("id"))
            ->pluck("quantity", "item_id");

        $settings = CompanySettingService::group(CompanySettingService::NUMERIC_VALIDATION);
        $precision = min(8, max(0, (int) ($settings["decimal_precision"] ?? 3)));
        $maxValue = (float) ($settings["default_max_value"] ?? 999999999999.999);
        $seen = [];

        foreach($enteredRows as $index => $row) {

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

            $unit = trim((string) $row->get("unidad_de_medida", ""));

            if($unit !== "" && mb_strtoupper($unit) !== mb_strtoupper((string) ($product->measurementUnit?->plural_name ?? "UNIDADES"))) {

                throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: la unidad de medida no coincide con el producto."]]);

            }

            if(isset($seen[$product->id])) {

                throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: el producto está repetido en la plantilla."]]);

            }

            $seen[$product->id] = true;
            $amount = $this->number($row->get($amountKey), $rowNumber, $this->operation["amount_heading"], $precision, $maxValue, $amountKey === "saldo_contado");
            $cost = null;

            if($this->operation["has_cost"] && $this->hasValue($row->get("costo_unitario_opcional"))) {

                $cost = $this->number($row->get("costo_unitario_opcional"), $rowNumber, "Costo unitario", $precision, $maxValue, true);

            }

            $this->items[] = [
                "item_id" => (int) $product->id,
                "internal_code" => $product->internal_code,
                "name" => $product->name,
                "unit_singular" => $product->measurementUnit?->singular_name ?? "UNIDAD",
                "unit_plural" => $product->measurementUnit?->plural_name ?? "UNIDADES",
                "current_stock" => (string) ($currentStock->get($product->id) ?? "0"),
                "amount" => $amount,
                "unit_cost" => $cost,
            ];

        }

    }

    public function items(): array {

        return $this->items;

    }

    private function hasValue(mixed $value): bool {

        return $value !== null && trim((string) $value) !== "";

    }

    private function number(mixed $value, int $rowNumber, string $label, int $precision, float $maxValue, bool $allowZero): string {

        $number = str_replace(",", ".", trim((string) $value));
        $pattern = $precision === 0
            ? '/^\d+$/D'
            : '/^\d+(?:\.\d{1,'.$precision.'})?$/D';

        if(!preg_match($pattern, $number) || (float) $number > $maxValue || (!$allowZero && (float) $number <= 0)) {

            $condition = $allowZero ? "no negativo" : "mayor que cero";

            throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: {$label} debe ser un número {$condition} con hasta {$precision} decimales."]]);

        }

        return $number;

    }

}
