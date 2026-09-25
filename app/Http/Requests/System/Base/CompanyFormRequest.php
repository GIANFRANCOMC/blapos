<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Base;

use App\Helpers\System\{ApiResponse};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Contracts\Validation\{Validator};
use Illuminate\Foundation\Http\{FormRequest};
use Illuminate\Http\Exceptions\{HttpResponseException};

/**
 * Shared request contract for modules isolated by the current tenant database.
 */
abstract class CompanyFormRequest extends FormRequest {
    public function authorize(): bool {

        return $this->user() !== null;

    }

    protected function prepareForValidation(): void {

        $normalized = [];

        foreach($this->normalizedStringFields() as $field) {

            if(!$this->exists($field)) {

                continue;

            }

            $value = $this->input($field);

            if(!is_string($value)) {

                continue;

            }

            $value = trim($value);

            $normalized[$field] = $value === "" ? null : $value;

        }

        if($normalized !== []) {

            $this->merge($normalized);

        }

    }

    /**
     * @return array<int, string>
     */
    protected function normalizedStringFields(): array {

        return [];

    }

    protected function decimalPrecision(): int {

        $precision = (int) ($this->numericValidationSettings()["decimal_precision"] ?? 3);

        return max(0, min(8, $precision));

    }

    protected function numericMinValue(): float {

        return (float) ($this->numericValidationSettings()["default_min_value"] ?? 0);

    }

    protected function numericMaxValue(): float {

        return (float) ($this->numericValidationSettings()["default_max_value"] ?? 999999999999.999);

    }

    protected function numericMaxFileSizeKb(): int {

        return max(1, (int) ($this->numericValidationSettings()["max_file_size_kb"] ?? 4096));

    }

    protected function decimalRule(): string {

        return "decimal:0,".$this->decimalPrecision();

    }

    protected function maxValueRule(?float $maxValue = null): string {

        return "max:".($maxValue ?? $this->numericMaxValue());

    }

    protected function minValueRule(?float $minValue = null): string {

        return "min:".($minValue ?? $this->numericMinValue());

    }

    protected function numericRules(
        ?float $min = null,
        ?float $max = null,
        bool $required = false,
        bool $decimal = true
    ): array {

        $rules = [$required ? "required" : "nullable", "numeric", $this->minValueRule($min), $this->maxValueRule($max)];

        if($decimal) {

            $rules[] = $this->decimalRule();

        }

        return $rules;

    }

    protected function positiveNumericRules(
        float $min = 0.0001,
        ?float $max = null,
        bool $required = true
    ): array {

        return $this->numericRules($min, $max, $required);

    }

    protected function normalizeDecimalInput(mixed $value): ?float {

        if($value === null || $value === "") {

            return null;

        }

        $normalized = is_string($value)
            ? str_replace(",", "", trim($value))
            : $value;

        return round((float) $normalized, $this->decimalPrecision());

    }

    protected function nullableIntegerInput(mixed $value): ?int {

        return $value === null || $value === "" ? null : (int) $value;

    }

    protected function nullableStringInput(mixed $value): ?string {

        return is_string($value) && trim($value) !== "" ? trim($value) : null;

    }

    protected function normalizeDecimalFromArray(array $data, string $field): ?float {

        return array_key_exists($field, $data)
            ? $this->normalizeDecimalInput($data[$field])
            : null;

    }

    protected function nullableIntegerFromArray(array $data, string $field): ?int {

        return array_key_exists($field, $data)
            ? $this->nullableIntegerInput($data[$field])
            : null;

    }

    protected function nullableStringFromArray(array $data, string $field): ?string {

        return $this->nullableStringInput($data[$field] ?? null);

    }

    private function numericValidationSettings(): array {

        return CompanySettingService::group(
            $this->companyId(),
            CompanySettingService::NUMERIC_VALIDATION
        );

    }

    protected function companyId(): int {

        return app(TenantCompanyContext::class)->id();

    }

    public function messages(): array {

        return [
            "required" => "Campo obligatorio.",
            "string" => "Ingresa un texto válido.",
            "numeric" => "Ingresa un número válido.",
            "integer" => "Ingresa un número entero válido.",
            "boolean" => "Selecciona una opción válida.",
            "array" => "Selecciona una opción válida.",
            "in" => "Selecciona una opción válida.",
            "date" => "Ingresa una fecha válida.",
            "ip" => "Ingresa una IP válida.",
            "url" => "Ingresa una URL válida.",
            "email" => "Ingresa un correo válido.",
            "distinct" => "No repitas la misma opción.",
            "different" => "Selecciona una opción diferente.",
            "required_with" => "Campo obligatorio.",
            "required_without" => "Campo obligatorio.",
            "required_without_all" => "Campo obligatorio.",
            "gt.numeric" => "Debe ser mayor que :value.",
            "gte.numeric" => "Debe ser mayor o igual a :value.",
            "min.numeric" => "El valor mínimo permitido es :min.",
            "max.numeric" => "El valor máximo permitido es :max.",
            "min.array" => "Selecciona al menos :min opción.",
            "max.array" => "Selecciona como máximo :max opciones.",
            "max.string" => "Debe tener como máximo :max caracteres.",
            "decimal" => "Usa hasta :decimal decimales.",
        ];

    }

    protected function failedValidation(Validator $validator): void {

        throw new HttpResponseException(
            ApiResponse::validationError(
                $validator->errors()->toArray(),
                "Revisa los campos marcados para continuar."
            )
        );

    }
}
