<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Warehouses;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class UpdateStockLevelsRequest extends CompanyFormRequest {

    public function rules(): array {

        // Un saldo negativo existente puede conservarse al editar solo el mínimo.
        $preservesNegative = (float) $this->input("quantity") < 0
            && (float) $this->input("quantity") === (float) $this->input("expected_quantity");

        return [
            "warehouse_id" => ["required", "integer", "min:1"],
            "item_id" => ["required", "integer", "min:1"],
            "quantity" => $this->numericRules(min: $preservesNegative ? "-".$this->numericMaxValue() : 0, required: true),
            "minimum_stock" => $this->numericRules(min: 0, required: true),
            "expected_quantity" => ["required", "numeric", $this->decimalRule()],
            "expected_minimum_stock" => $this->numericRules(min: 0, required: true),
            "reason" => ["nullable", "string", "max:100"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["reason"];

    }

}
