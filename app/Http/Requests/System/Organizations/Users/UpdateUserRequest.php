<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\Users;

use Illuminate\Foundation\Http\{FormRequest};

class UpdateUserRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {

        return true;

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {

        $userId = (int) $this->route("id");

        return UserValidationRules::forDocumentType((int) $this->identity_document_type_id, $userId, false);

    }
}
