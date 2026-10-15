<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Assets\AssetManagements;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class AssignAssetToBranchRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "branch_id" => ["required", "integer"],
            "branch_assets" => ["required", "array", "min:1"],
            "branch_assets.*.asset_id" => ["required", "integer"],
            "branch_assets.*.quantity" => ["required", "numeric", "min:0.0001", "max:{$maxValue}", "decimal:0,{$round}"],
        ];

    }

}
