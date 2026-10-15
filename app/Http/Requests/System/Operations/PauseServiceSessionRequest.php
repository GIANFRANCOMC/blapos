<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class PauseServiceSessionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "service_session_item_id" => ["nullable", "integer"],
            "reason" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["reason"];

    }

}
