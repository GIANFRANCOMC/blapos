<?php

declare(strict_types=1);

namespace App\Imports\System\Catalogs\References;

use App\Models\System\Catalogs\{Brand, Category};
use App\Services\System\Base\{InternalCodeService};
use App\Services\System\Catalogs\Brands\{BrandService};
use App\Services\System\Catalogs\Categories\{CategoryService};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\{Collection, Str};
use Illuminate\Validation\{Rule, ValidationException};
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToCollection, WithHeadingRow};

final class CatalogReferenceImport implements SkipsEmptyRows, ToCollection, WithHeadingRow {

    private int $importedCount = 0;

    public function __construct(
        private readonly string $type,
        private readonly int $userId
    ) {

        if(!in_array($type, ["brands", "categories"], true)) {

            throw new InvalidArgumentException("Tipo de catálogo no válido.");

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
                $validator = Validator::make($data, $this->rules());

                if($validator->fails()) {

                    $messages = [];

                    foreach($validator->errors()->toArray() as $field => $errors) {

                        $messages[] = "Fila {$rowNumber}, {$this->fieldLabel($field)}: {$errors[0]}";

                    }

                    throw ValidationException::withMessages(["file" => $messages]);

                }

                if($this->type === "brands") {

                    BrandService::create($data, $this->userId);

                }else {

                    CategoryService::create($data, $this->userId);

                }

                $this->importedCount++;

            }

        });

    }

    public function importedCount(): int {

        return $this->importedCount;

    }

    private function normalizeRow(array $row): array {

        $entity = $this->type === "brands" ? "brand" : "category";
        $code = trim((string) ($row["codigo_interno"] ?? ""));
        $base = [
            "internal_code" => InternalCodeService::applyPrefix($entity, $code !== "" ? $code : $this->generateCode($entity)),
            "name" => trim((string) ($row["nombre"] ?? "")),
            "description" => $this->optionalString($row["descripcion"] ?? null),
            "status" => $this->normalizeStatus($row["estado"] ?? null),
        ];

        if($this->type === "brands") {

            return $base + [
                "website_url" => $this->optionalString($row["sitio_oficial"] ?? null),
            ];

        }

        return $base;

    }

    private function rules(): array {

        $table = $this->type;

        $rules = [
            "internal_code" => ["required", "string", "max:50", "regex:/^[A-Za-z0-9._-]+$/", Rule::unique($table, "internal_code")],
            "name" => ["required", "string", "max:".($this->type === "brands" ? 100 : 50), Rule::unique($table, "name")],
            "description" => ["nullable", "string", "max:".($this->type === "brands" ? 250 : 100)],
            "status" => ["required", Rule::in(["active", "inactive"])],
        ];

        if($this->type === "brands") {

            return $rules + [
                "website_url" => ["nullable", "url", "max:500"],
            ];

        }

        return $rules;

    }

    private function generateCode(string $entity): string {

        $model = $this->type === "brands" ? Brand::class : Category::class;

        do {

            $code = InternalCodeService::applyPrefix($entity, Str::upper(Str::random(7)));

        } while($model::query()->where("internal_code", $code)->exists());

        return $code;

    }

    private function optionalString(mixed $value): ?string {

        $text = trim((string) ($value ?? ""));

        return $text === "" ? null : $text;

    }

    private function normalizeStatus(mixed $value): string {

        $status = mb_strtolower(trim((string) ($value ?? "")));

        return match ($status) {
            "", "activo", "activa", "active" => "active",
            "inactivo", "inactiva", "inactive" => "inactive",
            default => $status,
        };

    }

    private function fieldLabel(string $field): string {

        return [
            "internal_code" => "código interno",
            "name" => "nombre",
            "description" => "descripción",
            "status" => "estado",
            "website_url" => "sitio oficial",
        ][$field] ?? $field;

    }

}
