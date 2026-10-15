<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class ReassignServiceSessionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "assigned_user_id" => ["required", "integer"],
            "note" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["note"];

    }

}
