<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Categories;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Http\Requests\System\Concerns\{ValidatesInternalCodePrefix};
use App\Rules\System\Defaults\{UniqueInTenant};

abstract class CategoryRequest extends CompanyFormRequest {

    use ValidatesInternalCodePrefix;

    protected function internalCodeEntity(): string {

        return "category";

    }

    public function rules(): array {

        $categoryId = $this->route("id") ? (int) $this->route("id") : null;

        return [
            "internal_code" => [
                "bail",
                "required",
                "string",
                "max:50",
                $this->internalCodeWhitespaceRule(),
                $this->internalCodePrefixRule(),
                new UniqueInTenant("categories", "internal_code", $categoryId, [], "código interno"),
            ],
            "name" => [
                "required",
                "string",
                "max:50",
                new UniqueInTenant("categories", "name", $categoryId, [], "nombre"),
            ],
            "description" => ["nullable", "string", "max:100"],
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
            "name",
            "description",
        ];

    }


}
