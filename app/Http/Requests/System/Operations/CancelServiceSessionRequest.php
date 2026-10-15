<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class CancelServiceSessionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "reason" => ["required", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["reason"];

    }

}
