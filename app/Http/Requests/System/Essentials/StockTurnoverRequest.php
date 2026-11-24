<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Essentials;

use Illuminate\Support\Facades\{DB};
use Illuminate\Validation\{Validator};

final class StockTurnoverRequest extends AnalyticsPeriodRequest {

    public function rules(): array {

        return parent::rules() + [
            "warehouse_id" => ["nullable", "integer", "min:1"],
            "replenishment_only" => ["sometimes", "boolean"],
        ];

    }

    public function attributes(): array {

        return parent::attributes() + ["warehouse_id" => "Almacén"];

    }

    public function withValidator(Validator $validator): void {

        parent::withValidator($validator);

        $validator->after(function(Validator $validator): void {

            if($validator->errors()->hasAny(["branch_id", "warehouse_id"]) || !$this->filled(["branch_id", "warehouse_id"])) {
                return;
            }

            if(!DB::table("warehouses")->where("id", $this->input("warehouse_id"))
                ->where("branch_id", $this->input("branch_id"))->exists()) {
                $validator->errors()->add("warehouse_id", "Selecciona un almacén de la sucursal elegida.");
            }

        });

    }

}
