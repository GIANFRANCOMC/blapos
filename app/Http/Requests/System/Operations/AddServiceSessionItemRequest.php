<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class AddServiceSessionItemRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "item_id" => ["required", "integer"],
            "assigned_user_id" => ["nullable", "integer"],
            "quantity" => ["required", "numeric", "min:0.0001", "max:{$maxValue}", "decimal:0,{$round}"],
            "start_immediately" => ["nullable", "boolean"],
            "observation" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["observation"];

    }

}
