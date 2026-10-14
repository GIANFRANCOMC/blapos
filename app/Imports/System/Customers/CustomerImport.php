<?php

declare(strict_types=1);

namespace App\Imports\System\Customers;

use App\Http\Requests\System\Customers\Customers\{CustomerValidationRules};
use App\Models\System\General\{IdentityDocumentType};
use App\Services\System\Customers\Customers\{CustomerService};
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Support\{Collection};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Concerns\{SkipsEmptyRows, ToCollection, WithHeadingRow};

final class CustomerImport implements SkipsEmptyRows, ToCollection, WithHeadingRow {
    private int $importedCount = 0;

    public function __construct(private readonly int $userId) {
    }

    public function collection(Collection $rows): void {

        if($rows->isEmpty()) {

            throw ValidationException::withMessages(["file" => ["El archivo no contiene clientes para importar."]]);

        }

        if($rows->count() > 500) {

            throw ValidationException::withMessages(["file" => ["El archivo puede contener como máximo 500 clientes."]]);

        }

        $documentTypes = IdentityDocumentType::query()
            ->where("status", "active")
            ->get(["id", "code"])
            ->keyBy(fn($type) => strtolower((string) $type->code));

        DB::transaction(function() use ($rows, $documentTypes) {

            foreach($rows as $index => $row) {

                $rowNumber = $index + 2;
                $values = $row->toArray();
                $documentTypeCode = strtolower(trim((string) ($values["tipo_de_documento"] ?? "")));
                $documentTypeId = (int) ($documentTypes->get($documentTypeCode)?->id ?? 0);
                $data = [
                    "identity_document_type_id" => $documentTypeId ?: null,
                    "document_number" => trim((string) ($values["numero_de_documento"] ?? "")),
                    "name" => trim((string) ($values["nombre"] ?? "")),
                    "email" => $this->optionalText($values["correo_electronico"] ?? null),
                    "phone_number" => $this->optionalText($values["celular"] ?? null),
                    "gender" => $this->optionalText($values["genero"] ?? null),
                    "birthdate" => $this->optionalText($values["fecha_de_nacimiento"] ?? null),
                    "emergency_contact_name" => $this->optionalText($values["contacto_de_emergencia"] ?? null),
                    "emergency_contact_phone" => $this->optionalText($values["celular_de_emergencia"] ?? null),
                    "medical_notes" => $this->optionalText($values["observaciones_medicas"] ?? null),
                    "status" => strtolower($this->optionalText($values["estado"] ?? null) ?? "active"),
                ];

                $validator = Validator::make($data, CustomerValidationRules::forDocumentType($documentTypeId));

                if($validator->fails()) {

                    $messages = [];

                    foreach($validator->errors()->toArray() as $field => $errors) {

                        $messages[] = "Fila {$rowNumber}, {$field}: {$errors[0]}";

                    }

                    throw ValidationException::withMessages(["file" => $messages]);

                }

                CustomerService::create($data, $this->userId);
                $this->importedCount++;

            }

        });

    }

    public function importedCount(): int {

        return $this->importedCount;

    }

    private function optionalText(mixed $value): ?string {

        $text = trim((string) ($value ?? ""));

        return $text === "" ? null : $text;

    }
}
