<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Purchases;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class ReceivePurchaseRequest extends CompanyFormRequest {

    public function authorize(): bool {

        return parent::authorize();

    }

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "received_at" => ["required", "date"],
            "observation" => ["nullable", "string", "max:1000"],
            "items" => ["required", "array", "min:1"],
            "items.*.purchase_item_id" => ["required", "integer", "distinct"],
            "items.*.quantity" => ["required", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
        ];

    }

    public function messages(): array {

        return parent::messages() + [
            "required" => "Campo obligatorio.",
            "items.min" => "Agrega al menos un producto.",
            "distinct" => "No repitas un producto.",
            "numeric" => "Ingresa un número válido.",
            "gt" => "Debe ser mayor que cero.",
            "max" => "Supera la longitud permitida.",
        ];

    }

}
