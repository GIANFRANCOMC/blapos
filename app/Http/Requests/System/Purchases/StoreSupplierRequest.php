<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Purchases;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use Illuminate\Validation\{Rule};

final class StoreSupplierRequest extends CompanyFormRequest {
    public function authorize(): bool {

        return parent::authorize();

    }

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "document_type" => ["nullable", "string", "max:20"],
            "document_number" => [
                "nullable",
                "string",
                "max:30",
                Rule::unique("suppliers", "document_number")
                    ->ignore((int) $this->route("id")),
            ],
            "name" => ["required", "string", "max:255"],
            "contact_name" => ["nullable", "string", "max:255"],
            "telephone" => ["nullable", "string", "max:30"],
            "email" => ["nullable", "email", "max:255"],
            "address" => ["nullable", "string", "max:255"],
            "payment_term_days" => ["nullable", "integer", "min:0", "max:3650"],
            "credit_limit" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "contacts" => ["nullable", "array", "max:20"],
            "contacts.*.name" => ["required", "string", "max:255"],
            "contacts.*.position" => ["nullable", "string", "max:100"],
            "contacts.*.telephone" => ["nullable", "string", "max:30"],
            "contacts.*.email" => ["nullable", "email", "max:255"],
            "contacts.*.is_primary" => ["nullable", "boolean"],
            "bank_accounts" => ["nullable", "array", "max:20"],
            "bank_accounts.*.bank_name" => ["required", "string", "max:150"],
            "bank_accounts.*.currency_code" => ["required", "string", "max:10"],
            "bank_accounts.*.account_number" => ["required", "string", "max:100"],
            "bank_accounts.*.interbank_code" => ["nullable", "string", "max:100"],
            "bank_accounts.*.is_primary" => ["nullable", "boolean"],
            "status" => ["required", "in:active,inactive"],
        ];

    }

    public function messages(): array {

        return parent::messages() + [
            "required" => "Campo obligatorio.",
            "unique" => "Ya existe un proveedor con este número de documento.",
            "email" => "Ingresa un correo válido.",
            "in" => "Selecciona una opción válida.",
            "max" => "Supera la longitud permitida.",
        ];

    }
}
