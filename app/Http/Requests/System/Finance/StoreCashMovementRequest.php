<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Finance;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreCashMovementRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "cash_session_id" => ["required", "integer"],
            "payment_method_id" => ["nullable", "integer"],
            "movement_type" => ["required", "in:income,expense,adjustment"],
            "amount" => ["required", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "reference" => ["nullable", "string", "max:120"],
            "note" => ["nullable", "string", "max:300"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["movement_type", "reference", "note"];

    }

}
