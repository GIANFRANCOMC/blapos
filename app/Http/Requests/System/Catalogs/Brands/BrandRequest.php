<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Brands;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{UniqueInTenant};
use App\Services\System\Base\{InternalCodeService};

abstract class BrandRequest extends CompanyFormRequest {
    public function rules(): array {

        $brandId = $this->route("id") ? (int) $this->route("id") : null;

        return [
            "internal_code" => [
                "required",
                "string",
                "max:50",
                "regex:/^[A-Za-z0-9._-]+$/",
                new UniqueInTenant("brands", "internal_code", $brandId, [], "código interno"),
            ],
            "name" => [
                "required",
                "string",
                "max:100",
                new UniqueInTenant("brands", "name", $brandId, [], "nombre"),
            ],
            "description" => ["nullable", "string", "max:250"],
            "logo_path" => ["nullable", "string", "max:500"],
            "origin_country_code" => ["nullable", "string", "size:3"],
            "website_url" => ["nullable", "url", "max:500"],
            "status" => ["required", "in:active,inactive"],
        ];

    }

    public function attributes(): array {

        return [
            "internal_code" => "código interno",
            "name" => "nombre",
            "description" => "descripción",
            "status" => "estado",
        ];

    }

    public function messages(): array {

        return array_merge(parent::messages(), [
            "internal_code.regex" => "El código interno solo puede contener letras, números, puntos, guiones y guiones bajos.",
        ]);

    }

    protected function normalizedStringFields(): array {

        return [
            "internal_code",
            "name",
            "description",
            "logo_path",
            "origin_country_code",
            "website_url",
        ];

    }

    protected function prepareForValidation(): void {

        parent::prepareForValidation();

        $this->merge([
            "internal_code" => InternalCodeService::applyPrefix(
                "brand",
                $this->input("internal_code")
            ),
        ]);

    }
}
