<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Warehouses;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use Illuminate\Validation\{Rule, Validator};

final class StoreInventoryMovementRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "warehouse_id" => ["required", "integer"],
            "item_id" => ["required", "integer"],
            "movement_type" => ["required", "in:entry,exit,correction"],
            "origin_type" => ["required", Rule::in(InventoryMovementService::manualOperationOrigins((string) $this->input("movement_type")))],
            "quantity" => ["nullable", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "resulting_balance" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "reason" => ["required", "string", "max:255"],
        ];

    }

    public function after(): array {

        return [function(Validator $validator): void {

            $requiredField = $this->input("movement_type") === "correction"
                ? "resulting_balance"
                : "quantity";

            if(!$this->filled($requiredField)) {

                $validator->errors()->add($requiredField, "Campo obligatorio.");

            }

        }];

    }

    protected function normalizedStringFields(): array {

        return ["movement_type", "origin_type", "reason"];

    }

}
