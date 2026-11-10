<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Finance;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class OpenCashSessionRequest extends CompanyFormRequest {

    public function rules(): array {

        $decimalRule = $this->decimalRule();
        $maxValue = $this->numericMaxValue();

        return [
            "cash_register_id" => ["required", "integer"],
            "opening_amount" => ["nullable", "numeric", "min:0", "max:{$maxValue}", $decimalRule],
            "observation" => ["nullable", "string", "max:300"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["observation"];

    }

}
