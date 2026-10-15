<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\Customers;

use Illuminate\Foundation\Http\{FormRequest};

final class StoreCustomerRequest extends FormRequest {

    public function authorize(): bool {

        return true;

    }

    public function rules(): array {

        $identityTypeId = (int) $this->input("identity_document_type_id");

        return CustomerValidationRules::forDocumentType($identityTypeId);

    }

}
