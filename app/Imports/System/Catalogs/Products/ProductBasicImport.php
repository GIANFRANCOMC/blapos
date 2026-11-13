<?php

declare(strict_types=1);

namespace App\Imports\System\Catalogs\Products;

use App\Helpers\System\{Numbers};
use App\Models\System\Catalogs\{Item};
use App\Rules\System\Catalogs\{ValidEan13};
use App\Services\System\Base\{InternalCodeService};
use App\Services\System\Catalogs\Products\{ProductService};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\Collection;
use Illuminate\Validation\{Rule, ValidationException};
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToCollection, WithHeadingRow};

final class ProductBasicImport implements SkipsEmptyRows, ToCollection, WithHeadingRow {

    private int $importedCount = 0;

    public function __construct(
        private readonly int $currencyId,
        private readonly int $warehouseId,
        private readonly int $userId
    ) {
    }

    public function collection(Collection $rows): void {

        if($rows->count() > 500) {

            throw ValidationException::withMessages([
                "file" => ["El archivo puede contener como máximo 500 productos."],
            ]);

        }

        DB::transaction(function() use ($rows) {

            foreach($rows as $index => $row) {

                $rowNumber = $index + 2;
                $data = $this->normalizeRow($row->toArray());
                $validator = $this->validator($data);

                if($validator->fails()) {

                    $errors = [];

                    foreach($validator->errors()->toArray() as $field => $messages) {

                        $errors["file"][] = "Fila {$rowNumber}, {$this->fieldLabel($field)}: {$messages[0]}";

                    }

                    throw ValidationException::withMessages($errors);

                }

                ProductService::create([
                    "internal_code" => $data["internal_code"],
                    "barcode" => $data["barcode"],
                    "name" => $data["name"],
                    "description" => $data["description"],
                    "price" => (float) $data["price"],
                    "min_price" => null,
                    "max_price" => null,
                    "currency_id" => $this->currencyId,
                    "brand_id" => null,
                    "categories" => [],
                    "see_my_web" => false,
                    "see_my_web_price" => false,
                    "status" => "active",
                    "inventory" => [[
                        "warehouse_id" => $this->warehouseId,
                        "initial_stock" => (float) ($data["initial_stock"] ?? 0),
                        "minimum_stock" => (float) ($data["minimum_stock"] ?? 0),
                    ]],
                ], $this->userId);

                $this->importedCount++;

            }

        });

    }

    public function importedCount(): int {

        return $this->importedCount;

    }

    private function normalizeRow(array $row): array {

        $internalCode = (string) ($row["codigo_interno"] ?? "");
        $barcode = (string) ($row["codigo_de_barras"] ?? "");

        return [
            "internal_code" => $internalCode,
            "barcode" => $barcode !== "" ? $barcode : $this->generateBarcode(),
            "name" => trim((string) ($row["nombre"] ?? "")),
            "description" => trim((string) ($row["descripcion"] ?? "")) ?: null,
            "price" => Numbers::normalizeDecimalInput($row["precio"] ?? null),
            "initial_stock" => Numbers::normalizeDecimalInput($row["stock_inicial"] ?? null) ?? 0,
            "minimum_stock" => Numbers::normalizeDecimalInput($row["stock_minimo"] ?? null) ?? 0,
        ];

    }

    private function validator(array $data) {

        return Validator::make($data, [
            "internal_code" => [
                "bail",
                "required",
                "string",
                "max:50",
                'regex:/\A[A-Za-z0-9._-]+\z/',
                InternalCodeService::prefixRule("product"),
                Rule::unique("items", "internal_code")
                    ->where("type", "product"),
            ],
            "barcode" => [
                "required",
                "string",
                new ValidEan13(),
                Rule::unique("items", "barcode"),
            ],
            "name" => ["required", "string", "max:100"],
            "description" => ["nullable", "string", "max:100"],
            "price" => Numbers::numericRules(min: Numbers::decimalStep(), required: true),
            "initial_stock" => Numbers::numericRules(min: 0, required: true),
            "minimum_stock" => Numbers::numericRules(min: 0, required: true),
        ], [
            "required" => "Campo obligatorio.",
            "numeric" => "Ingresa un número válido.",
            "decimal" => Numbers::decimalError(),
            "min" => "Debe ser mayor o igual al mínimo permitido.",
            "max.numeric" => "El valor máximo permitido es :max.",
            "max.string" => "Supera la longitud permitida.",
            "unique" => "Ya existe un producto con este valor.",
            "regex" => "Contiene caracteres no permitidos.",
        ]);

    }

    private function generateBarcode(): string {

        do {

            $base = "200".str_pad((string) random_int(0, 999999999), 9, "0", STR_PAD_LEFT);
            $barcode = $base.$this->ean13CheckDigit($base);

        } while(Item::query()
            ->where("barcode", $barcode)
            ->exists());

        return $barcode;

    }

    private function ean13CheckDigit(string $base): int {

        $sum = 0;

        foreach(str_split($base) as $index => $digit) {

            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);

        }

        return (10 - ($sum % 10)) % 10;

    }

    private function fieldLabel(string $field): string {

        return [
            "internal_code" => "código interno",
            "barcode" => "código de barras",
            "name" => "nombre",
            "description" => "descripción",
            "price" => "precio",
            "initial_stock" => "stock inicial",
            "minimum_stock" => "stock mínimo",
        ][$field] ?? $field;

    }

}
