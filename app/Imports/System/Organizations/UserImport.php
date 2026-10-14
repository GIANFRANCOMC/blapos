<?php

declare(strict_types=1);

namespace App\Imports\System\Organizations;

use App\Http\Requests\System\Organizations\Users\{UserValidationRules};
use App\Models\System\General\{IdentityDocumentType};
use App\Models\System\Organizations\{Role};
use App\Services\System\Organizations\Users\{UserService};
use Illuminate\Auth\Access\{AuthorizationException};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\{Collection, Str};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToCollection, WithHeadingRow};
use PhpOffice\PhpSpreadsheet\Shared\{Date};

final class UserImport implements SkipsEmptyRows, ToCollection, WithHeadingRow {
    private int $importedCount = 0;

    public function __construct(private readonly int $actorId) {
    }

    public function collection(Collection $rows): void {

        if($rows->isEmpty()) {

            throw ValidationException::withMessages(["file" => ["El archivo no contiene colaboradores para importar."]]);

        }

        if($rows->count() > 500) {

            throw ValidationException::withMessages(["file" => ["El archivo puede contener como máximo 500 colaboradores."]]);

        }

        $roles = Role::query()->where("status", "active")->get(["id", "slug", "name"]);
        $documentTypes = IdentityDocumentType::query()->where("status", "active")->get(["id", "code", "name"]);

        DB::transaction(function() use ($rows, $roles, $documentTypes) {

            foreach($rows as $index => $row) {

                $rowNumber = $index + 2;
                $values = $row->toArray();
                $roleId = $this->referenceId($roles, $values["perfil"] ?? null, ["slug", "name"]);
                $documentTypeId = $this->referenceId($documentTypes, $values["tipo_de_documento"] ?? null, ["code", "name"]);
                $data = [
                    "role_id" => $roleId,
                    "identity_document_type_id" => $documentTypeId,
                    "document_number" => trim((string) ($values["numero_de_documento"] ?? "")),
                    "name" => trim((string) ($values["nombre"] ?? "")),
                    "email" => Str::lower(trim((string) ($values["correo_electronico"] ?? ""))),
                    "phone_number" => $this->optionalText($values["celular"] ?? null),
                    "gender" => $this->optionalText($values["genero"] ?? null),
                    "birthdate" => $this->dateText($values["fecha_de_nacimiento"] ?? null),
                    "status" => "inactive",
                    "password" => Str::random(18)."A1",
                    "branch_scope_mode" => "inherit",
                    "cash_register_scope_mode" => "inherit",
                    "warehouse_scope_mode" => "inherit",
                    "branch_ids" => [],
                    "cash_register_ids" => [],
                    "warehouse_ids" => [],
                ];

                $validator = Validator::make($data, UserValidationRules::forDocumentType((int) $documentTypeId));

                if($validator->fails()) {

                    $messages = [];

                    foreach($validator->errors()->toArray() as $field => $errors) {

                        $messages[] = "Fila {$rowNumber}, {$field}: {$errors[0]}";

                    }

                    throw ValidationException::withMessages(["file" => $messages]);

                }

                try {

                    UserService::create($data, $this->actorId);

                }catch(AuthorizationException $exception) {

                    throw ValidationException::withMessages(["file" => ["Fila {$rowNumber}: {$exception->getMessage()}"]]);

                }

                $this->importedCount++;

            }

        });

    }

    public function importedCount(): int {

        return $this->importedCount;

    }

    private function referenceId(Collection $items, mixed $value, array $fields): ?int {

        $needle = Str::lower(trim((string) ($value ?? "")));

        if($needle === "") {

            return null;

        }

        $matches = $items->filter(function($item) use ($needle, $fields) {

            foreach($fields as $field) {

                if(Str::lower(trim((string) $item->{$field})) === $needle) {

                    return true;

                }

            }

            return false;

        });

        return $matches->count() === 1 ? (int) $matches->first()->id : null;

    }

    private function optionalText(mixed $value): ?string {

        $text = trim((string) ($value ?? ""));

        return $text === "" ? null : $text;

    }

    private function dateText(mixed $value): ?string {

        if(is_numeric($value) && (float) $value > 10000 && (float) $value < 100000) {

            return Date::excelToDateTimeObject((float) $value)->format("Y-m-d");

        }

        return $this->optionalText($value);

    }
}
