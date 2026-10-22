<?php

declare(strict_types=1);

namespace App\Imports\System\Catalogs\Offerings;

use App\Models\System\Catalogs\{Item};
use App\Services\System\Base\{InternalCodeService};
use App\Services\System\Catalogs\Services\{ServiceService};
use App\Services\System\Catalogs\Subscriptions\{SubscriptionService};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\{Collection, Str};
use Illuminate\Validation\{Rule, ValidationException};
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToCollection, WithHeadingRow};

final class OfferingBasicImport implements SkipsEmptyRows, ToCollection, WithHeadingRow {

    private int $importedCount = 0;

    public function __construct(
        private readonly string $type,
        private readonly int $currencyId,
        private readonly int $userId
    ) {

        if(!in_array($type, [Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION], true)) {

            throw new \InvalidArgumentException("Tipo de oferta no compatible.");

        }

    }

    public function collection(Collection $rows): void {

        if($rows->isEmpty() || $rows->count() > 500) {

            throw ValidationException::withMessages([
                "file" => ["El archivo debe contener entre 1 y 500 registros."],
            ]);

        }

        DB::transaction(function() use ($rows) {

            foreach($rows as $index => $row) {

                $rowNumber = $index + 2;
                $data = $this->normalizeRow($row->toArray());
                $validator = Validator::make($data, $this->rules(), [
                    "required" => "Campo obligatorio.",
                    "numeric" => "Ingresa un número válido.",
                    "integer" => "Ingresa un número entero.",
                    "min" => "Debe ser mayor o igual al mínimo permitido.",
                    "max" => "Supera el límite permitido.",
                    "unique" => "Ya existe un registro con este código interno.",
                    "in" => "Selecciona un tipo de duración válido.",
                    "regex" => "Contiene caracteres no permitidos.",
                ]);

                if($validator->fails()) {

                    $errors = [];

                    foreach($validator->errors()->toArray() as $field => $messages) {

                        $errors["file"][] = "Fila {$rowNumber}, {$this->fieldLabel($field)}: {$messages[0]}";

                    }

                    throw ValidationException::withMessages($errors);

                }

                $attributes = [
                    "internal_code" => $data["internal_code"],
                    "name" => $data["name"],
                    "description" => $data["description"],
                    "price" => (float) $data["price"],
                    "price_includes_tax" => true,
                    "igv_exempt" => false,
                    "min_price" => null,
                    "max_price" => null,
                    "currency_id" => $this->currencyId,
                    "categories" => [],
                    "see_my_web" => false,
                    "see_my_web_price" => false,
                    "status" => "active",
                ];

                if($this->type === Item::TYPE_SERVICE) {

                    $attributes["estimated_duration_minutes"] = $data["estimated_duration_minutes"];
                    ServiceService::create($attributes, $this->userId);

                }else {

                    $attributes["duration_value"] = (int) $data["duration_value"];
                    $attributes["duration_type"] = $data["duration_type"];
                    SubscriptionService::create($attributes, $this->userId);

                }

                $this->importedCount++;

            }

        });

    }

    public function importedCount(): int {

        return $this->importedCount;

    }

    private function normalizeRow(array $row): array {

        $providedCode = trim((string) ($row["codigo_interno"] ?? ""));
        $durationType = Str::lower(trim((string) ($row["duracion_tipo"] ?? "")));
        $estimatedDuration = $row["duracion_estimada_minutos"] ?? null;
        $durationTypes = [
            "hora" => "hour", "día" => "day", "dia" => "day", "rutina" => "today",
            "mes" => "month", "año" => "year", "ano" => "year"
        ];

        return [
            "internal_code" => $providedCode !== ""
                ? InternalCodeService::applyPrefix($this->type, $providedCode)
                : $this->generateInternalCode(),
            "name" => trim((string) ($row["nombre"] ?? "")),
            "description" => trim((string) ($row["descripcion"] ?? "")) ?: null,
            "price" => $row["precio"] ?? null,
            "estimated_duration_minutes" => $estimatedDuration === "" ? null : $estimatedDuration,
            "duration_value" => $row["duracion_valor"] ?? null,
            "duration_type" => $durationTypes[$durationType] ?? $durationType,
        ];

    }

    private function rules(): array {

        $rules = [
            "internal_code" => [
                "required", "string", "max:50", "regex:/^[A-Za-z0-9._-]+$/",
                Rule::unique("items", "internal_code")->where("type", $this->type),
            ],
            "name" => ["required", "string", "max:100"],
            "description" => ["nullable", "string", "max:100"],
            "price" => ["required", "numeric", "min:0.1"],
        ];

        if($this->type === Item::TYPE_SERVICE) {

            $rules["estimated_duration_minutes"] = ["nullable", "integer", "min:1", "max:10080"];

        }else {

            $rules["duration_value"] = ["required", "integer", "min:1"];
            $rules["duration_type"] = ["required", Rule::in(["hour", "day", "today", "month", "year"])];

        }

        return $rules;

    }

    private function generateInternalCode(): string {

        do {

            $code = InternalCodeService::applyPrefix($this->type, Str::upper(Str::random(7)));

        }while(Item::query()->where("type", $this->type)->where("internal_code", $code)->exists());

        return $code;

    }

    private function fieldLabel(string $field): string {

        return [
            "internal_code" => "código interno",
            "name" => "nombre",
            "description" => "descripción",
            "price" => "precio",
            "estimated_duration_minutes" => "duración estimada",
            "duration_value" => "duración (valor)",
            "duration_type" => "duración (tipo)",
        ][$field] ?? $field;

    }

}
