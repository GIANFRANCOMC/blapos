<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Finance;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class CloseCashSessionRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "cash_session_id" => ["required", "integer"],
            "counted_amount" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "payments" => ["nullable", "array", "max:50"],
            "payments.*.payment_method_id" => ["nullable", "integer"],
            "payments.*.counted_amount" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "inventory_counts" => ["nullable", "array", "max:500"],
            "inventory_counts.*.warehouse_id" => ["required_with:inventory_counts", "integer"],
            "inventory_counts.*.item_id" => ["required_with:inventory_counts", "integer"],
            "inventory_counts.*.counted_quantity" => ["required_with:inventory_counts", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "inventory_counts.*.observation" => ["nullable", "string", "max:500"],
            "observation" => ["nullable", "string", "max:300"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["observation"];

    }

}
