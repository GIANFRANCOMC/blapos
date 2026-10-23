<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\CsvSeedDataset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use RuntimeException;

final class DemoPeopleSeeder extends Seeder {

    public const CUSTOMER_COUNT = 10000;

    public const COLLABORATOR_COUNT = 10000;

    private const BATCH_SIZE = 250;

    // Solo cuentas demo inactivas con claves aleatorias de 32 caracteres; la clave del administrador usa el costo normal.
    private const INACTIVE_PASSWORD_ROUNDS = 4;

    private const CUSTOMER_COLUMNS = [
        "identity_document_type_id", "document_number", "name", "email",
        "phone_number", "emergency_contact_name", "emergency_contact_phone",
        "medical_notes", "gender", "birthdate", "status", "created_at",
        "created_by", "updated_at", "updated_by",
    ];

    private const USER_COLUMNS = [
        "role_id", "branch_scope_mode", "cash_register_scope_mode",
        "warehouse_scope_mode", "identity_document_type_id", "document_number",
        "name", "email", "email_verified_at", "password", "remember_token",
        "session_version", "phone_number", "gender", "birthdate", "status",
        "created_at", "created_by", "updated_at", "updated_by",
    ];

    public function run(?int $customerLimit = null, ?int $collaboratorLimit = null): void {

        $customerLimit ??= self::CUSTOMER_COUNT;
        $collaboratorLimit ??= self::COLLABORATOR_COUNT;

        if(
            $customerLimit < 0
            || $customerLimit > self::CUSTOMER_COUNT
            || $collaboratorLimit < 0
            || $collaboratorLimit > self::COLLABORATOR_COUNT
        ) {

            throw new RuntimeException("Los límites solicitados superan el tamaño de los datasets de personas.");

        }

        DB::transaction(function() use ($customerLimit, $collaboratorLimit): void {

            $adminId = (int) DB::table("users")
                ->where("status", "active")
                ->orderBy("id")
                ->value("id");
            $documentTypeId = (int) DB::table("identity_document_types")
                ->where("code", "dni")
                ->value("id");

            if(!$adminId || !$documentTypeId) {

                throw new RuntimeException("Aprovisiona el administrador y DNI antes de cargar las personas de prueba.");

            }

            $this->seedCustomers($adminId, $documentTypeId, $customerLimit);
            $this->seedCollaborators($adminId, $documentTypeId, $collaboratorLimit);

        });

    }

    private function seedCustomers(int $adminId, int $documentTypeId, int $limit): void {

        $path = __DIR__."/data/05_clientes_10000.csv";
        $documents = [];
        $emails = [];
        $existingCustomers = DB::table("customers")
            ->where("identity_document_type_id", $documentTypeId)
            ->pluck("email", "document_number")
            ->all();
        $batch = [];

        foreach(CsvSeedDataset::rows($path, self::CUSTOMER_COLUMNS, self::CUSTOMER_COUNT) as $number => $row) {

            $person = $this->normalizePerson($row, $documentTypeId, $path, $number);
            $document = $person["document_number"];
            $email = $person["email"];

            if(isset($documents[$document]) || ($email !== null && isset($emails[$email]))) {

                throw new RuntimeException("Cliente duplicado en fila ".($number + 1).".");

            }

            $documents[$document] = true;

            if($email !== null) {

                $emails[$email] = true;

            }

            $emergencyName = $this->optionalText($row["emergency_contact_name"], 255);
            $emergencyPhone = $this->optionalText($row["emergency_contact_phone"], 50);
            $medicalNotes = $this->optionalText($row["medical_notes"], 5000);
            $status = $this->status($row["status"], ["active", "inactive"], $number);

            if(($emergencyName === null) !== ($emergencyPhone === null)) {

                throw new RuntimeException("El contacto de emergencia está incompleto en fila ".($number + 1).".");

            }

            if($number > $limit) {

                continue;

            }

            if(array_key_exists($document, $existingCustomers)) {

                if($existingCustomers[$document] !== $email) {

                    throw new RuntimeException("El DNI {$document} ya pertenece a otro cliente.");

                }

                continue;

            }

            $batch[] = $person + [
                "emergency_contact_name" => $emergencyName,
                "emergency_contact_phone" => $emergencyPhone,
                "medical_notes" => $medicalNotes,
                "status" => $status,
                "created_at" => now()->toDateTimeString(),
                "created_by" => $adminId,
                "updated_at" => now()->toDateTimeString(),
                "updated_by" => $adminId,
            ];

            if(count($batch) === self::BATCH_SIZE) {

                DB::table("customers")->insertOrIgnore($batch);
                $batch = [];

            }

        }

        if($batch) {

            DB::table("customers")->insertOrIgnore($batch);

        }

    }

    private function seedCollaborators(int $adminId, int $documentTypeId, int $limit): void {

        $roleIds = app(AccessProfilesSeeder::class)->run($adminId);

        $path = __DIR__."/data/04_usuarios_10000.csv";
        $documents = [];
        $emails = [];
        $existingUsers = DB::table("users")
            ->get(["email", "document_number"])
            ->keyBy("email");
        $existingDocuments = DB::table("users")
            ->pluck("email", "document_number")
            ->all();
        $batch = [];

        foreach(CsvSeedDataset::rows($path, self::USER_COLUMNS, self::COLLABORATOR_COUNT) as $number => $row) {

            $person = $this->normalizePerson($row, $documentTypeId, $path, $number);
            $document = $person["document_number"];
            $email = $person["email"];

            if($email === null || isset($documents[$document]) || isset($emails[$email])) {

                throw new RuntimeException("Colaborador incompleto o duplicado en fila ".($number + 1).".");

            }

            $documents[$document] = true;
            $emails[$email] = true;
            $this->status($row["status"], ["active", "inactive", "blocked"], $number);

            foreach(["branch_scope_mode", "cash_register_scope_mode", "warehouse_scope_mode"] as $column) {

                if($row[$column] !== "inherit") {

                    throw new RuntimeException("Ámbito no soportado en fila ".($number + 1).".");

                }

            }

            if($number > $limit) {

                continue;

            }

            if(isset($existingUsers[$email])) {

                if($existingUsers[$email]->document_number !== $document) {

                    throw new RuntimeException("El correo {$email} ya pertenece a otro colaborador.");

                }

                continue;

            }

            if(isset($existingDocuments[$document])) {

                throw new RuntimeException("El DNI {$document} ya pertenece a otro colaborador.");

            }

            $batch[] = $person + [
                "role_id" => AccessProfilesSeeder::roleIdForEmail($email, $roleIds),
                "branch_scope_mode" => "inherit",
                "cash_register_scope_mode" => "inherit",
                "warehouse_scope_mode" => "inherit",
                "email_verified_at" => null,
                "password" => Hash::make(
                    Str::random(32),
                    ["rounds" => self::INACTIVE_PASSWORD_ROUNDS]
                ),
                "session_version" => 1,
                "status" => "inactive",
                "created_at" => now()->toDateTimeString(),
                "created_by" => $adminId,
                "updated_at" => now()->toDateTimeString(),
                "updated_by" => $adminId,
            ];

            if(count($batch) === self::BATCH_SIZE) {

                DB::table("users")->insertOrIgnore($batch);
                $batch = [];

            }

        }

        if($batch) {

            DB::table("users")->insertOrIgnore($batch);

        }

    }

    private function normalizePerson(array $row, int $documentTypeId, string $path, int $number): array {

        $document = trim($row["document_number"]);
        $name = trim($row["name"]);
        $email = strtolower(trim($row["email"]));
        $phone = trim($row["phone_number"]);
        $gender = trim($row["gender"]);
        $birthdate = trim($row["birthdate"]);

        if(
            trim($row["identity_document_type_id"]) !== "1"
            || !preg_match('/^\d{1,8}$/', $document)
            || $name === ""
            || mb_strlen($name) > 100
            || ($email !== "" && (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)))
            || mb_strlen($phone) > 15
            || ($gender !== "" && !in_array($gender, ["male", "female", "other"], true))
            || (
                $birthdate !== ""
                && (
                    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)
                    || !checkdate(
                        (int) substr($birthdate, 5, 2),
                        (int) substr($birthdate, 8, 2),
                        (int) substr($birthdate, 0, 4)
                    )
                )
            )
        ) {

            throw new RuntimeException("Documento o datos personales inválidos en ".basename($path).", fila ".($number + 1).".");

        }

        return [
            "identity_document_type_id" => $documentTypeId,
            "document_number" => str_pad($document, 8, "0", STR_PAD_LEFT),
            "name" => $name,
            "email" => $email === "" ? null : $email,
            "phone_number" => $phone === "" ? null : $phone,
            "gender" => $gender === "" ? null : $gender,
            "birthdate" => $birthdate === "" ? null : $birthdate,
        ];

    }

    private function optionalText(string $value, int $maxLength): ?string {

        $value = trim($value);

        if(mb_strlen($value) > $maxLength) {

            throw new RuntimeException("Un texto del dataset excede el límite permitido.");

        }

        return $value === "" ? null : $value;

    }

    private function status(string $value, array $allowed, int $number): string {

        if(!in_array($value, $allowed, true)) {

            throw new RuntimeException("Estado inválido en fila ".($number + 1).".");

        }

        return $value;

    }

}
