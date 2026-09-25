<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\Branches;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Http\Requests\System\Concerns\{AppliesInternalCodePrefix};
use App\Rules\System\Defaults\{UniqueInTenant};

class StoreBranchRequest extends CompanyFormRequest {
    use AppliesInternalCodePrefix;

    protected function internalCodeEntity(): string {

        return "branch";

    }

    protected function normalizedStringFields(): array {

        return [
            "internal_code",
            "name",
            "address",
            "reference",
            "telephone",
            "email",
            "map_url",
        ];

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {

        $validations = [
            "internal_code" => ["required", "string", "max:50", new UniqueInTenant("branches", "internal_code", null, [], "código interno")],
            "name" => "required|string|max:50",
            "address" => "nullable|string|max:100",
            "reference" => "nullable|string|max:100",
            "telephone" => "nullable|string|max:15",
            "email" => "nullable|email|max:100",
            "capacity" => "nullable|integer|min:0",
            "map_url" => "nullable|url|max:500",
            "status" => "required|in:active,inactive",
        ];

        return $validations;

    }
}
