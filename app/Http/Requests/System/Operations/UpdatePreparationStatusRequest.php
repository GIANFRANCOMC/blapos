<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class UpdatePreparationStatusRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "status" => ["required", "in:preparing,ready,delivered"],
        ];

    }

}
