<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class AddServiceSessionItemRequest extends CompanyFormRequest {

    public function rules(): array {

        $decimalRule = $this->decimalRule();
        $maxValue = $this->numericMaxValue();

        return [
            "item_id" => ["required", "integer"],
            "assigned_user_id" => ["nullable", "integer"],
            "quantity" => ["required", "numeric", "gt:0", "max:{$maxValue}", $decimalRule],
            "start_immediately" => ["nullable", "boolean"],
            "observation" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["observation"];

    }

}
