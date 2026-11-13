<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Subscriptions;

use App\Helpers\System\{Numbers};
use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Http\Requests\System\Concerns\{ValidatesInternalCodePrefix};
use App\Rules\System\Catalogs\{DelimitedArrayTextLength};
use App\Rules\System\Defaults\{ExistsInTenant, UniqueInTenant};
use Illuminate\Validation\{Validator};

class UpdateSubscriptionRequest extends CompanyFormRequest {

    use ValidatesInternalCodePrefix;

    protected function internalCodeEntity(): string {

        return "subscription";

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

        $itemId = (int) $this->route("id");
        $decimalRule = $this->decimalRule();
        $minimumPrice = $this->input("min_price");
        $maximumPrice = $this->input("max_price");
        $companyMaximum = $this->numericMaxValue();
        $minimumBound = is_numeric($minimumPrice) && (float) $minimumPrice > 0 ? $minimumPrice : 0;
        $minValue = $minimumBound ?: Numbers::decimalStep();
        $maxValue = is_numeric($maximumPrice) && (float) $maximumPrice > 0 ? $maximumPrice : $companyMaximum;

        $validations = [
            "internal_code" => ["bail", "required", "string", "max:50", $this->internalCodeWhitespaceRule(), $this->internalCodePrefixRule(), new UniqueInTenant("items", "internal_code", $itemId, ["type" => "subscription"], "código interno")],
            "name" => "required|string|max:100",
            "description" => "nullable|string|max:100",
            "duration_value" => "required|integer|min:1|max:$maxValue",
            "duration_type" => "required|in:hour,day,today,month,year",
            "price" => "required|numeric|min:$minValue|max:$maxValue|$decimalRule",
            "min_price" => "nullable|numeric|min:0|max:$companyMaximum|$decimalRule",
            "max_price" => "nullable|numeric|min:$minimumBound|max:$companyMaximum|$decimalRule",
            "price_includes_tax" => "nullable|boolean",
            "igv_exempt" => "nullable|boolean",
            "commission_type" => "nullable|in:none,percentage,fixed",
            "commission_value" => "nullable|numeric|min:0|max:$maxValue|$decimalRule",
            "currency_id" => ["required", "integer", new ExistsInTenant("currencies", ["status" => "active"], "La moneda seleccionada no pertenece a la empresa.")],
            "capacity_control_enabled" => "nullable|boolean",
            "capacity_limit" => "nullable|integer|min:1|max:1000000",
            "expires_at" => "nullable|date",
            "attendance_limit_per_day" => "nullable|integer|min:1|max:1000",
            "benefits" => ["nullable", "array", "max:50", new DelimitedArrayTextLength(100, "beneficios")],
            "benefits.*" => "string|max:100",
            "restrictions" => ["nullable", "array", "max:50", new DelimitedArrayTextLength(100, "restricciones")],
            "restrictions.*" => "string|max:100",
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
            "commission_type" => $this->input("commission_type") ?: "none",
            "commission_value" => $this->input("commission_type") === "none"
                ? 0
                : ($this->normalizeDecimalInput($this->input("commission_value")) ?? 0),
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

    private function validateCommission(Validator $validator): void {

        if($validator->errors()->hasAny(["commission_type", "commission_value"])) {

            return;

        }

        $type = (string) $this->input("commission_type", "none");

        $value = (float) ($this->input("commission_value") ?? 0);

        if($type === "percentage" && $value > 100) {

            $validator->errors()->add("commission_value", "No puede superar el 100%.");

        }

    }

}
