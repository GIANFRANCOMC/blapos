<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class UpdateServiceStationLayoutRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();

        return [
            "service_floor_id" => ["nullable", "integer"],
            "position_x" => ["nullable", "numeric", "min:0", "max:100", "decimal:0,{$round}"],
            "position_y" => ["nullable", "numeric", "min:0", "max:100", "decimal:0,{$round}"],
            "color" => ["nullable", "regex:/^#[0-9a-fA-F]{6}$/"],
            "shape" => ["nullable", "in:round,square,rectangle"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["color", "shape"];

    }

}
