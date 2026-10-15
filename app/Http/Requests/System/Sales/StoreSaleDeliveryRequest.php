<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Sales;

use App\Http\Requests\System\Base\{CompanyFormRequest};

class StoreSaleDeliveryRequest extends CompanyFormRequest {

    protected function prepareForValidation(): void {

        $this->merge([
            "warehouse_id" => $this->nullableIntegerInput($this->input("warehouse_id")),
            "items" => collect($this->input("items", []))
                ->map(function($item) {

                    if(!is_array($item)) {

                        return $item;

                    }

                    $item["sale_delivery_item_id"] = $this->nullableIntegerFromArray($item, "sale_delivery_item_id");

                    $item["quantity"] = $this->normalizeDecimalFromArray($item, "quantity");

                    return $item;

                })
                ->values()
                ->all(),
        ]);

    }

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "warehouse_id" => "required|integer",
            "delivered_at" => "nullable|date",
            "observation" => "nullable|string|max:500",
            "items" => "required|array|min:1|max:100",
            "items.*.sale_delivery_item_id" => "required|integer",
            "items.*.quantity" => "required|numeric|min:0|max:$maxValue|decimal:0,$round",
        ];

    }

    public function messages(): array {

        return [
            "warehouse_id.required" => "Selecciona el almacén desde donde se entregará.",
            "items.required" => "Indica al menos un producto a entregar.",
            "items.*.quantity.numeric" => "La cantidad debe ser un número válido.",
            "items.*.quantity.max" => "La cantidad supera el máximo permitido.",
        ];

    }

}
