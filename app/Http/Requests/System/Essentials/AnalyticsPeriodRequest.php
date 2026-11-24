<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Essentials;

use Carbon\{CarbonImmutable};
use Illuminate\Foundation\Http\{FormRequest};
use Illuminate\Validation\{Validator};

abstract class AnalyticsPeriodRequest extends FormRequest {

    public function rules(): array {

        return [
            "from" => ["required", "date_format:Y-m-d"],
            "to" => ["required", "date_format:Y-m-d", "after_or_equal:from"],
            "branch_id" => ["nullable", "integer", "min:1"],
            "page" => ["sometimes", "integer", "min:1", "max:2147483647"],
        ];

    }

    public function attributes(): array {

        return ["from" => "Desde", "to" => "Hasta", "branch_id" => "Sucursal"];

    }

    public function withValidator(Validator $validator): void {

        $validator->after(function(Validator $validator): void {

            if($validator->errors()->hasAny(["from", "to"])) {
                return;
            }

            $from = CarbonImmutable::parse($this->input("from"), "UTC");
            $to = CarbonImmutable::parse($this->input("to"), "UTC");

            if($to->greaterThan($from->addDays(365))) {
                $validator->errors()->add("to", "Selecciona un periodo de hasta 366 días.");
            }

        });

    }

}
