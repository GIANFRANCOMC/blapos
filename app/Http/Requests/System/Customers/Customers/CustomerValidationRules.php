<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\Customers;

use App\Rules\System\Defaults\{DocumentNumberLength, ExistsInTenant, UniqueInTenant};

final class CustomerValidationRules {

    public static function forDocumentType(int $identityTypeId, ?int $customerId = null): array {

        return [
            "identity_document_type_id" => ["required", "integer", new ExistsInTenant("identity_document_types", ["status" => "active"], "El tipo de documento no pertenece a la empresa.")],
            "document_number" => ["required", "string", new DocumentNumberLength($identityTypeId), new UniqueInTenant("customers", "document_number", $customerId, ["identity_document_type_id" => $identityTypeId], "número de documento")],
            "name" => ["required", "string", "max:100"],
            "email" => ["nullable", "email", "max:100"],
            "phone_number" => ["nullable", "string", "max:15"],
            "emergency_contact_name" => ["nullable", "string", "max:255", "required_with:emergency_contact_phone"],
            "emergency_contact_phone" => ["nullable", "string", "max:50", "required_with:emergency_contact_name"],
            "medical_notes" => ["nullable", "string", "max:5000"],
            "gender" => ["nullable", "in:male,female,other"],
            "birthdate" => ["nullable", "date"],
            "status" => ["required", "in:active,inactive"],
        ];

    }

}
