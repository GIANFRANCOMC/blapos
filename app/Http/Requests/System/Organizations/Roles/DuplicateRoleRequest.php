<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\Roles;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use Illuminate\Validation\{Rule};

final class DuplicateRoleRequest extends CompanyFormRequest {
    public function rules(): array {

        return [
            "name" => [
                "required",
                "string",
                "max:80",
                Rule::unique("roles", "name"),
            ],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["name"];

    }
}
