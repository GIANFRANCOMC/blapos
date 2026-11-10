<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Assets\AssetManagements;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class UpdateAssetInBranchRequest extends CompanyFormRequest {

    public function rules(): array {

        $decimalRule = $this->decimalRule();
        $maxValue = $this->numericMaxValue();

        return [
            "branch_id" => ["required", "integer"],
            "id" => ["required", "integer"],
            "asset_id" => ["required", "integer"],
            "quantity" => ["required", "numeric", "min:0", "max:{$maxValue}", $decimalRule],
            "acquisition_value" => ["nullable", "numeric", "min:0", "max:{$maxValue}", $decimalRule],
            "acquisition_date" => ["nullable", "date"],
            "note" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["note"];

    }

}
