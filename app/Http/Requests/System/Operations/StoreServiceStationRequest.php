<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreServiceStationRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();

        return [
            "branch_id" => ["required", "integer"],
            "service_floor_id" => ["nullable", "integer"],
            "code" => ["required", "string", "max:50"],
            "name" => ["required", "string", "max:150"],
            "station_type" => ["required", "string", "max:30"],
            "capacity" => ["required", "integer", "min:1", "max:999"],
            "position_x" => ["nullable", "numeric", "min:0", "max:100", "decimal:0,{$round}"],
            "position_y" => ["nullable", "numeric", "min:0", "max:100", "decimal:0,{$round}"],
            "color" => ["nullable", "regex:/^#[0-9a-fA-F]{6}$/"],
            "shape" => ["nullable", "in:round,square,rectangle"],
            "description" => ["nullable", "string", "max:500"],
            "status" => ["nullable", "in:active,inactive"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["code", "name", "station_type", "color", "shape", "description", "status"];

    }

}
