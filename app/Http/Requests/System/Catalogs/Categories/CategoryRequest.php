<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Categories;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{UniqueInTenant};
use App\Services\System\Base\{InternalCodeService};

abstract class CategoryRequest extends CompanyFormRequest {
    public function rules(): array {

        $categoryId = $this->route("id") ? (int) $this->route("id") : null;

        return [
            "internal_code" => [
                "required",
                "string",
                "max:50",
                new UniqueInTenant("categories", "internal_code", $categoryId, [], "código interno"),
            ],
            "name" => [
                "required",
                "string",
                "max:50",
                new UniqueInTenant("categories", "name", $categoryId, [], "nombre"),
            ],
            "description" => ["nullable", "string", "max:100"],
            "sort_order" => ["nullable", "integer", "min:1", "max:9999"],
            "is_public" => ["nullable", "boolean"],
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

    protected function normalizedStringFields(): array {

        return [
            "internal_code",
            "name",
            "description",
        ];

    }

    protected function prepareForValidation(): void {

        parent::prepareForValidation();

        $this->merge([
            "internal_code" => InternalCodeService::applyPrefix(
                "category",
                $this->input("internal_code")
            ),
        ]);

    }
}
