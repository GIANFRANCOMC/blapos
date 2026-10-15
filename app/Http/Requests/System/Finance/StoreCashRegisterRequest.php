<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Finance;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreCashRegisterRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "branch_id" => ["required", "integer"],
            "code" => ["nullable", "string", "max:30"],
            "name" => ["required", "string", "max:100"],
            "is_main" => ["nullable", "boolean"],
            "status" => ["required", "in:active,inactive"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["code", "name", "status"];

    }

}
