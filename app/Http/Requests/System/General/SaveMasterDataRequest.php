<?php

declare(strict_types=1);

namespace App\Http\Requests\System\General;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class SaveMasterDataRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();
        $maxFileSizeKb = $this->numericMaxFileSizeKb();

        return match ((string) $this->route("resource")) {
            "identity-documents" => [
                "code" => ["required", "string", "max:50"],
                "name" => ["required", "string", "max:100"],
                "is_searchable" => ["required", "boolean"],
                "min_length" => ["required", "integer", "min:1", "max:100"],
                "max_length" => ["required", "integer", "gte:min_length", "max:100"],
                "status" => ["required", "in:active,inactive"],
            ],
            "currencies" => [
                "code" => ["required", "string", "max:10"],
                "sign" => ["required", "string", "max:10"],
                "singular_name" => ["required", "string", "max:50"],
                "plural_name" => ["required", "string", "max:50"],
                "status" => ["required", "in:active,inactive"],
            ],
            "taxes" => [
                "code" => ["required", "string", "max:30"],
                "name" => ["required", "string", "max:255"],
                "description" => ["nullable", "string", "max:500"],
                "rate" => ["required", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
                "calculation_type" => ["required", "in:percentage,fixed"],
                "operation_type" => ["required", "in:addition,subtraction"],
                "min_apply_quantity" => ["nullable", "integer", "min:0"],
                "max_apply_quantity" => ["nullable", "integer", "gte:min_apply_quantity"],
                "scope" => ["required", "in:sale,purchase"],
                "is_required" => ["required", "boolean"],
                "is_default" => ["required", "boolean"],
                "status" => ["required", "in:active,inactive"],
            ],
            "payment-methods" => [
                "code" => ["required", "string", "max:30"],
                "name" => ["required", "string", "max:255"],
                "sunat_code" => ["nullable", "string", "max:10"],
                "image" => ["nullable", "image", "mimes:jpg,jpeg,png,webp", "max:{$maxFileSizeKb}"],
                "scope" => ["required", "in:sale,purchase,both"],
                "requires_reference" => ["required", "boolean"],
                "is_default" => ["required", "boolean"],
                "status" => ["required", "in:active,inactive"],
            ],
            "company-settings" => [
                "group" => ["required", "string", "max:255"],
                "key" => ["required", "string", "max:255"],
                "value" => ["nullable"],
                "description" => ["nullable", "string", "max:500"],
                "value_type" => ["required", "in:string,boolean,integer,decimal,json"],
                "status" => ["required", "in:active,inactive"],
            ],
            default => [
                "code" => ["required", "string", "max:50"],
                "name" => ["required", "string", "max:100"],
                "status" => ["required", "in:active,inactive"],
            ]
        };

    }

    protected function normalizedStringFields(): array {

        return [
            "code",
            "name",
            "description",
            "sign",
            "singular_name",
            "plural_name",
            "sunat_code",
            "scope",
            "group",
            "key",
            "value_type",
            "status",
        ];

    }

}
