<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Assets\AssetManagements;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class UnassignAssetFromUserRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "branch_id" => ["required", "integer"],
            "branch_asset_id" => ["required", "integer"],
            "asset_id" => ["required", "integer"],
            "assignments" => ["required", "array", "min:1"],
            "assignments.*.id" => ["required", "integer"],
            "assignments.*.user_id" => ["required", "integer"],
        ];

    }

}
