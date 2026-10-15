<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Assets\AssetManagements;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class UnassignAssetFromBranchRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "branch_id" => ["required", "integer"],
            "branch_assets" => ["required", "array", "min:1"],
            "branch_assets.*.id" => ["required", "integer"],
            "branch_assets.*.asset_id" => ["required", "integer"],
        ];

    }

}
