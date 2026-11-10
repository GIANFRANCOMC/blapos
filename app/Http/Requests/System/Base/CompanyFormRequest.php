<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Base;

use App\Helpers\System\{ApiResponse, Numbers};
use Illuminate\Contracts\Validation\{Validator};
use Illuminate\Foundation\Http\{FormRequest};
use Illuminate\Http\Exceptions\{HttpResponseException};
use Illuminate\Validation\Validator as LaravelValidator;

/**
 * Shared request contract for modules isolated by the current tenant database.
 */
abstract class CompanyFormRequest extends FormRequest {

    public function authorize(): bool {

        return $this->user() !== null;

    }

    public function withValidator(LaravelValidator $validator): void {

        Numbers::prepareValidator($validator);

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

    protected function numericMinValue(): string {

        return Numbers::minValue();

    }

    protected function numericMaxValue(): string {

        return Numbers::maxValue();

    }

    protected function numericMaxFileSizeKb(): int {

        return Numbers::maxFileSizeKb();

    }

    protected function decimalRule(): string {

        return Numbers::decimalRule();

    }

    protected function numericRules(
        float|string|null $min = null,
        float|string|null $max = null,
        bool $required = false,
        bool $decimal = true
    ): array {

        return Numbers::numericRules($min, $max, $required, $decimal);

    }

    protected function normalizeDecimalInput(mixed $value): mixed {

        return Numbers::normalizeDecimalInput($value);

    }

    protected function nullableIntegerInput(mixed $value): ?int {

        return $value === null || $value === "" ? null : (int) $value;

    }

    protected function nullableStringInput(mixed $value): ?string {

        return is_string($value) && trim($value) !== "" ? trim($value) : null;

    }

    protected function normalizeDecimalFromArray(array $data, string $field): mixed {

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
            "decimal" => Numbers::decimalError(),
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
