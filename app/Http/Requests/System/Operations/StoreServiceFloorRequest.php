<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreServiceFloorRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "branch_id" => ["required", "integer"],
            "code" => ["required", "string", "max:50"],
            "name" => ["required", "string", "max:150"],
            "level_number" => ["required", "integer", "min:-20", "max:200"],
            "sort_order" => ["nullable", "integer", "min:1", "max:999"],
            "background_color" => ["nullable", "regex:/^#[0-9a-fA-F]{6}$/"],
            "description" => ["nullable", "string", "max:500"],
            "status" => ["nullable", "in:active,inactive"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["code", "name", "background_color", "description", "status"];

    }

}
