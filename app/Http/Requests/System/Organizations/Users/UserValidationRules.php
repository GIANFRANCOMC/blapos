<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\Users;

use App\Rules\System\Defaults\{DocumentNumberLength, ExistsInTenant, UniqueInTenant};

final class UserValidationRules {

    public static function forDocumentType(int $documentTypeId, ?int $userId = null, bool $requirePassword = true): array {

        $rules = [
            "role_id" => ["required", "integer", new ExistsInTenant("roles", ["status" => "active"], null)],
            "identity_document_type_id" => ["required", "integer", new ExistsInTenant("identity_document_types", ["status" => "active"], "El tipo de documento no pertenece a la empresa.")],
            "document_number" => ["required", "string", new DocumentNumberLength($documentTypeId), new UniqueInTenant("users", "document_number", $userId, [], "número de documento")],
            "name" => "required|string|max:100",
            "email" => ["required", "email", "max:100", new UniqueInTenant("users", "email", $userId, [], "correo electrónico")],
            "phone_number" => "nullable|string|max:15",
            "gender" => "nullable|in:male,female,other",
            "birthdate" => "nullable|date",
            "status" => "required|in:active,inactive,blocked",
            "branch_ids" => "nullable|array",
            "branch_ids.*" => ["integer", "distinct", new ExistsInTenant("branches", [], null)],
            "cash_register_ids" => "nullable|array",
            "cash_register_ids.*" => ["integer", "distinct", new ExistsInTenant("cash_registers", [], null)],
            "warehouse_ids" => "nullable|array",
            "warehouse_ids.*" => ["integer", "distinct", new ExistsInTenant("warehouses", [], null)],
        ];

        if($requirePassword) {

            $rules["password"] = "required|string|max:20";

        }

        return $rules;

    }

}
