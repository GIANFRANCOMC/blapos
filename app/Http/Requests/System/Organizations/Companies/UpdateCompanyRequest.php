<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\Companies;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant, DocumentNumberLength};

class UpdateCompanyRequest extends CompanyFormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {

        $maxSize = $this->numericMaxFileSizeKb();

        $validations = [
            "identity_document_type_id" => ["required", "integer", new ExistsInTenant("identity_document_types", ["status" => "active"], "El tipo de documento no pertenece a la empresa.")],
            "document_number" => ["required", "string", new DocumentNumberLength((int) $this->identity_document_type_id)],
            "legal_name" => "required|string|max:100",
            "commercial_name" => "required|string|max:100",
            "tagline" => "nullable|string|max:200",
            "description" => "nullable|string|max:200",
            "address" => "nullable|string|max:100",
            "telephone" => "nullable|string|max:15",
            "email" => "nullable|email|max:100",
            "status" => "required|in:active,inactive",
            "logotype" => "nullable|file|image|mimes:jpeg,png,jpg|max:$maxSize",
            "combinationmark" => "nullable|file|image|mimes:jpeg,png,jpg|max:$maxSize",
            "logomark" => "nullable|file|image|mimes:jpeg,png,jpg|max:$maxSize",
            "login_image" => "nullable|file|image|mimes:jpeg,png,jpg|max:$maxSize",
        ];

        return $validations;

    }
}
