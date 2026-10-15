<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Warehouses;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use Illuminate\Validation\{Validator};

final class StoreInventoryOperationRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "warehouse_id" => ["required", "integer"],
            "movement_type" => ["required", "in:entry,exit,correction"],
            "origin_type" => ["required", "in:manual,replenishment,customer_return,supplier_return,physical_count"],
            "items" => ["required", "array", "min:1", "max:100"],
            "items.*.item_id" => ["required", "integer", "distinct"],
            "items.*.quantity" => ["nullable", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "items.*.resulting_balance" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "items.*.unit_cost" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "reason" => ["required", "string", "max:255"],
        ];

    }

    public function after(): array {

        return [function(Validator $validator): void {

            $requiredField = $this->input("movement_type") === "correction"
                ? "resulting_balance"
                : "quantity";

            foreach($this->input("items", []) as $index => $item) {

                if(!array_key_exists($requiredField, $item)
                    || $item[$requiredField] === null
                    || $item[$requiredField] === "") {

                    $validator->errors()->add("items.{$index}.{$requiredField}", "Campo obligatorio.");

                }

            }

        }];

    }

    protected function normalizedStringFields(): array {

        return ["movement_type", "origin_type", "reason"];

    }

}
