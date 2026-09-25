<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Purchases;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class StorePurchaseRequest extends CompanyFormRequest {
    public function authorize(): bool {

        return parent::authorize();

    }

    protected function normalizedStringFields(): array {

        return [
            "document_series",
            "document_number",
            "observation",
        ];

    }

    protected function prepareForValidation(): void {

        parent::prepareForValidation();

        $this->merge([
            "supplier_id" => $this->nullableInteger("supplier_id"),
            "warehouse_id" => $this->nullableInteger("warehouse_id"),
            "currency_id" => $this->nullableInteger("currency_id"),
            "document_type" => $this->input("document_type") ?: "order",
            "document_series" => $this->nullableString("document_series"),
            "delivery_mode" => $this->input("delivery_mode") ?: "immediate",
            "payment_modality" => $this->input("payment_modality") ?: "paid_now",
            "approval_status" => $this->input("approval_status") ?: "approved",
            "expected_date" => $this->nullableString("expected_date"),
            "due_date" => $this->nullableString("due_date"),
            "tax" => $this->nullableDecimal("tax"),
            "taxes" => $this->normalizeTaxes(),
            "payments" => $this->normalizePayments(),
            "expenses" => $this->normalizeExpenses(),
            "items" => $this->normalizeItems(),
        ]);

    }

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "supplier_id" => [
                "required",
                "integer",
                new ExistsInTenant("suppliers", ["status" => "active"], "Selecciona un proveedor activo de tu empresa."),
            ],
            "warehouse_id" => [
                "required",
                "integer",
                new ExistsInTenant(
                    "warehouses",
                    ["warehouses.status" => "active", "branches.status" => "active"],
                    "Selecciona un almacén activo de tu empresa.",
                    [["branches", "warehouses.branch_id", "=", "branches.id"]],
                    "warehouses.id"
                ),
            ],
            "currency_id" => [
                "required",
                "integer",
                new ExistsInTenant("currencies", ["status" => "active"], "La moneda seleccionada no pertenece a la empresa."),
            ],
            "document_type" => ["required", "in:order,invoice"],
            "document_series" => ["nullable", "string", "max:20"],
            "document_number" => ["nullable", "string", "max:50"],
            "issue_date" => ["required", "date"],
            "expected_date" => ["nullable", "date", "after_or_equal:issue_date"],
            "due_date" => ["nullable", "date", "after_or_equal:issue_date"],
            "approval_status" => ["nullable", "in:pending,approved"],
            "delivery_mode" => ["nullable", "in:immediate,pending"],
            "payment_modality" => ["nullable", "in:paid_now,cash_on_delivery,installments"],
            "installment_count" => ["nullable", "integer", "min:1", "max:120"],
            "first_due_date" => ["nullable", "date", "after_or_equal:issue_date"],
            "tax" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],

            "taxes" => ["nullable", "array", "max:20"],
            "taxes.*.tax_id" => [
                "required_with:taxes",
                "integer",
                new ExistsInTenant("taxes", ["status" => "active", "scope" => "purchase"], "Selecciona un tributo activo de compras."),
            ],
            "taxes.*.rate" => ["nullable", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "taxes.*.calculation_type" => ["nullable", "in:percentage,fixed"],
            "taxes.*.operation_type" => ["nullable", "in:addition,subtraction"],
            "taxes.*.is_required" => ["nullable", "boolean"],
            "taxes.*.quantity" => ["nullable", "integer", "min:1", "max:{$maxValue}"],
            "taxes.*.amount" => ["nullable", "numeric", "min:-{$maxValue}", "max:{$maxValue}", "decimal:0,{$round}"],

            "payments" => ["nullable", "array", "max:20"],
            "payments.*.payment_method_id" => [
                "required_with:payments",
                "integer",
                new ExistsInTenant("payment_methods", ["status" => "active"], "Selecciona un método de pago activo."),
            ],
            "payments.*.payment_method_variant_id" => ["nullable", "integer"],
            "payments.*.amount" => ["required_with:payments", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "payments.*.reference" => ["nullable", "string", "max:100"],
            "payments.*.note" => ["nullable", "string", "max:300"],

            "expenses" => ["nullable", "array", "max:20"],
            "expenses.*.expense_type" => ["required_with:expenses", "string", "max:40"],
            "expenses.*.name" => ["required_with:expenses", "string", "max:150"],
            "expenses.*.amount" => ["required_with:expenses", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "expenses.*.allocation_method" => ["nullable", "in:value,quantity,equal"],
            "expenses.*.note" => ["nullable", "string", "max:500"],

            "observation" => ["nullable", "string", "max:1000"],
            "items" => ["required", "array", "min:1", "max:100"],
            "items.*.item_id" => [
                "required",
                "integer",
                "distinct",
                new ExistsInTenant("items", ["type" => "product", "status" => "active"], "Selecciona un producto activo de tu empresa."),
            ],
            "items.*.quantity" => ["required", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "items.*.unit_cost" => ["required", "numeric", "min:0", "max:{$maxValue}", "decimal:0,{$round}"],
        ];

    }

    public function messages(): array {

        return parent::messages() + [
            "items.min" => "Agrega al menos un producto.",
            "items.max" => "Puedes agregar hasta 100 productos.",
            "items.*.item_id.distinct" => "No repitas un producto.",
            "after_or_equal" => "No puede ser anterior a la fecha de emisión.",
            "gt" => "Debe ser mayor que cero.",
            "min" => "No puede ser menor que cero.",
            "max" => "Supera la longitud permitida.",
        ];

    }

    private function normalizeItems(): array {

        return collect($this->input("items", []))
            ->map(fn($item) => [
                "item_id" => $this->nullableIntegerFromArray($item, "item_id"),
                "quantity" => $this->normalizeDecimalFromArray($item, "quantity"),
                "unit_cost" => $this->normalizeDecimalFromArray($item, "unit_cost"),
            ])
            ->values()
            ->all();

    }

    private function normalizeTaxes(): array {

        return collect($this->input("taxes", []))
            ->map(fn($tax) => [
                "tax_id" => $this->nullableIntegerFromArray($tax, "tax_id"),
                "rate" => $this->normalizeDecimalFromArray($tax, "rate"),
                "calculation_type" => $tax["calculation_type"] ?? null,
                "operation_type" => $tax["operation_type"] ?? null,
                "is_required" => filter_var($tax["is_required"] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
                "quantity" => $this->nullableIntegerFromArray($tax, "quantity"),
                "amount" => $this->normalizeDecimalFromArray($tax, "amount"),
            ])
            ->values()
            ->all();

    }

    private function normalizePayments(): array {

        return collect($this->input("payments", []))
            ->map(fn($payment) => [
                "payment_method_id" => $this->nullableIntegerFromArray($payment, "payment_method_id"),
                "payment_method_variant_id" => $this->nullableIntegerFromArray($payment, "payment_method_variant_id"),
                "amount" => $this->normalizeDecimalFromArray($payment, "amount"),
                "reference" => $this->nullableStringFromArray($payment, "reference"),
                "note" => $this->nullableStringFromArray($payment, "note"),
            ])
            ->values()
            ->all();

    }

    private function normalizeExpenses(): array {

        return collect($this->input("expenses", []))
            ->map(fn($expense) => [
                "expense_type" => $this->nullableStringFromArray($expense, "expense_type"),
                "name" => $this->nullableStringFromArray($expense, "name"),
                "amount" => $this->normalizeDecimalFromArray($expense, "amount"),
                "allocation_method" => $expense["allocation_method"] ?? null,
                "note" => $this->nullableStringFromArray($expense, "note"),
            ])
            ->values()
            ->all();

    }

    private function nullableInteger(string $field): ?int {

        return $this->filled($field) ? (int) $this->input($field) : null;

    }

    private function nullableDecimal(string $field): ?float {

        return $this->filled($field) ? $this->normalizeDecimalInput($this->input($field)) : null;

    }

    private function nullableString(string $field): ?string {

        $value = $this->input($field);

        return is_string($value) && trim($value) !== "" ? trim($value) : null;

    }
}
