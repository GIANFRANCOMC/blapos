<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Assets\AssetManagements;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class AssignAssetToUserRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "branch_id" => ["required", "integer"],
            "branch_asset_id" => ["required", "integer"],
            "asset_id" => ["required", "integer"],
            "assignments" => ["required", "array", "min:1"],
            "assignments.*.id" => ["nullable", "integer"],
            "assignments.*.user_id" => ["required", "integer"],
            "assignments.*.quantity" => ["required", "numeric", "min:0.0001", "max:{$maxValue}", "decimal:0,{$round}"],
        ];

    }

}
