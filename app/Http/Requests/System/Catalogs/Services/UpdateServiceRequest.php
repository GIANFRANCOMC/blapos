<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Services;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Http\Requests\System\Concerns\{AppliesInternalCodePrefix};
use App\Rules\System\Defaults\{ExistsInTenant, UniqueInTenant};
use Illuminate\Validation\{Validator};

class UpdateServiceRequest extends CompanyFormRequest {
    use AppliesInternalCodePrefix;

    protected function internalCodeEntity(): string {

        return "service";

    }

    protected function normalizedStringFields(): array {

        return ["internal_code", "name", "description"];

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {

        $itemId = (int) $this->route("id");
        $round = $this->decimalPrecision();
        $minValue = $this->filled("min_price") && (float) $this->input("min_price") > 0 ? (float) $this->input("min_price") : 0.1;
        $maxValue = $this->filled("max_price") && (float) $this->input("max_price") > 0 ? (float) $this->input("max_price") : $this->numericMaxValue();

        $validations = [
            "internal_code" => ["required", "string", "max:50", new UniqueInTenant("items", "internal_code", $itemId, ["type" => "service"], "código interno")],
            "name" => "required|string|max:50",
            "description" => "nullable|string|max:100",
            "price" => "required|numeric|min:$minValue|max:$maxValue|decimal:0,$round",
            "price_includes_tax" => "nullable|boolean",
            "igv_exempt" => "nullable|boolean",
            "currency_id" => ["required", "integer", new ExistsInTenant("currencies", ["status" => "active"], "La moneda seleccionada no pertenece a la empresa.")],
            "estimated_duration_minutes" => "nullable|integer|min:1|max:10080",
            "capacity_control_enabled" => "nullable|boolean",
            "capacity_limit" => "nullable|integer|min:1|max:1000000",
            "expires_at" => "nullable|date",
            "commission_rate" => "nullable|numeric|min:0|max:100|decimal:0,$round",
            "commission_type" => "nullable|in:none,percentage,fixed",
            "commission_value" => "nullable|numeric|min:0|max:$maxValue|decimal:0,$round",
            "status" => "required|in:active,inactive",
        ];

        if($this->filled("min_price") && (float) $this->input("min_price") > 0) {

            $validations["max_price"] = "nullable|numeric|min:$minValue|decimal:0,$round";

        }

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
