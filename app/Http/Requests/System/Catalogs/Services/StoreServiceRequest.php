<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Services;

use App\Helpers\System\{Numbers};
use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Http\Requests\System\Concerns\{ValidatesCatalogCommission, ValidatesInternalCodePrefix};
use App\Rules\System\Defaults\{ExistsInTenant, UniqueInTenant};
use Illuminate\Validation\{Validator};

class StoreServiceRequest extends CompanyFormRequest {

    use ValidatesCatalogCommission, ValidatesInternalCodePrefix;

    protected function internalCodeEntity(): string {

        return "service";

    }

    protected function normalizedStringFields(): array {

        return ["name", "description"];

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {

        $decimalRule = $this->decimalRule();
        $minimumPrice = $this->input("min_price");
        $maximumPrice = $this->input("max_price");
        $companyMaximum = $this->numericMaxValue();
        $minimumBound = is_numeric($minimumPrice) && (float) $minimumPrice > 0 ? $minimumPrice : 0;
        $minValue = $minimumBound ?: Numbers::decimalStep();
        $maxValue = is_numeric($maximumPrice) && (float) $maximumPrice > 0 ? $maximumPrice : $companyMaximum;

        $validations = [
            "internal_code" => ["bail", "required", "string", "max:50", $this->internalCodeWhitespaceRule(), $this->internalCodePrefixRule(), new UniqueInTenant("items", "internal_code", null, ["type" => "service"], "código interno")],
            "name" => "required|string|max:100",
            "description" => "nullable|string|max:100",
            "price" => "required|numeric|min:$minValue|max:$maxValue|$decimalRule",
            "min_price" => "nullable|numeric|min:0|max:$companyMaximum|$decimalRule",
            "max_price" => "nullable|numeric|min:$minimumBound|max:$companyMaximum|$decimalRule",
            "price_includes_tax" => "nullable|boolean",
            "igv_exempt" => "nullable|boolean",
            "currency_id" => ["required", "integer", new ExistsInTenant("currencies", ["status" => "active"], "La moneda seleccionada no pertenece a la empresa.")],
            "estimated_duration_minutes" => "nullable|integer|min:1|max:10080",
            "capacity_control_enabled" => "nullable|boolean",
            "capacity_limit" => "nullable|integer|min:1|max:1000000",
            "expires_at" => "nullable|date",
            "commission_rate" => "nullable|numeric|min:0|max:100|$decimalRule",
            "commission_type" => "nullable|in:none,percentage,fixed",
            "commission_value" => $this->numericRules(min: 0),
            "status" => "required|in:active,inactive",
        ];

        return $validations;

    }

    public function after(): array {

        return [

            function(Validator $validator) {

                $this->validateCommission($validator);
                $this->validateCapacity($validator);

            },
        ];

    }

    protected function prepareForValidation(): void {

        parent::prepareForValidation();

        $capacityEnabled = $this->boolean("capacity_control_enabled");

        $this->merge([
            "capacity_control_enabled" => $capacityEnabled,
            "capacity_limit" => $capacityEnabled ? $this->input("capacity_limit") : null,
            "expires_at" => $this->filled("expires_at") ? $this->input("expires_at") : null,
            "price" => $this->normalizeDecimalInput($this->input("price")),
            "min_price" => $this->normalizeDecimalInput($this->input("min_price")),
            "max_price" => $this->normalizeDecimalInput($this->input("max_price")),
            "commission_rate" => $this->normalizeDecimalInput($this->input("commission_rate")),
            ...$this->normalizedCommission(),
        ]);

    }

    private function validateCapacity(Validator $validator): void {

        if($validator->errors()->has("capacity_limit") || !$this->boolean("capacity_control_enabled")) {

            return;

        }

        if(!$this->filled("capacity_limit")) {

            $validator->errors()->add("capacity_limit", "Indica cuántos cupos estarán disponibles.");

        }

    }

}
