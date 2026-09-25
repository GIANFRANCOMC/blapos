<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Assets\Assets;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Http\Requests\System\Concerns\{AppliesInternalCodePrefix};
use App\Rules\System\Defaults\{ExistsInTenant, UniqueInTenant};

final class UpdateAssetRequest extends CompanyFormRequest {
    use AppliesInternalCodePrefix;

    protected function internalCodeEntity(): string {

        return "asset";

    }

    protected function normalizedStringFields(): array {

        return ["internal_code", "patrimonial_code", "serial_number", "name", "description"];

    }

    public function rules(): array {

        $assetId = (int) $this->route("id");

        return [
            "internal_code" => ["required", "string", "max:50", new UniqueInTenant("assets", "internal_code", $assetId, [], "código interno")],
            "asset_category_id" => ["nullable", "integer", new ExistsInTenant("asset_categories", ["status" => "active"])],
            "patrimonial_code" => ["nullable", "string", "max:100", new UniqueInTenant("assets", "patrimonial_code", $assetId)],
            "serial_number" => ["nullable", "string", "max:150", new UniqueInTenant("assets", "serial_number", $assetId)],
            "name" => ["required", "string", "max:50"],
            "description" => ["nullable", "string", "max:500"],
            "status" => ["required", "in:active,inactive"],
        ];

    }
}
