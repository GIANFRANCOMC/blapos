<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Sales;

use App\Helpers\System\{ApiResponse};
use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};
use Illuminate\Contracts\Validation\{Validator};
use Illuminate\Http\Exceptions\{HttpResponseException};

class StoreSaleRequest extends CompanyFormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {

        return parent::authorize();

    }

    protected function prepareForValidation(): void {

        $this->merge([
            "delivery_method_id" => $this->nullableIntegerInput($this->input("delivery_method_id")),
            "delivery_status" => $this->input("delivery_status")
                ?: ($this->input("delivery_mode") === "pending" ? "pending" : "delivered"),
            "payment_modality" => $this->input("payment_modality") ?: "paid_now",
            "taxes" => $this->normalizeTaxes(),
            "payments" => $this->normalizePayments(),
            "details" => $this->normalizeDetails(),
        ]);

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            // Header
            "branch_id" => "required|integer",
            "serie_id" => "required|integer",
            "holder_id" => "required|integer",
            "seller_id" => ["nullable", "integer", new ExistsInTenant("users", ["status" => "active"], "El vendedor seleccionado no pertenece a la empresa.")],
            "currency_id" => ["required", "integer", new ExistsInTenant("currencies", ["status" => "active"], "La moneda seleccionada no pertenece a la empresa.")],
            "warehouse_id" => "nullable|integer",
            "cash_session_id" => "nullable|integer",
            "quotation_header_id" => "nullable|integer",
            "service_session_id" => "nullable|integer",
            "source_channel" => "nullable|in:sale,pos",
            "issue_date" => "required|date",
            "delivery_mode" => "nullable|in:immediate,pending",
            "delivery_method_id" => ["nullable", "integer", new ExistsInTenant("sale_delivery_methods", ["status" => "active"], "La modalidad de entrega no pertenece a la empresa.")],
            "delivery_status" => "required|in:pending,delivered",
            "delivery_observation" => "nullable|string|max:500",
            "payment_modality" => "nullable|in:paid_now,cash_on_delivery,installments",
            "installment_count" => "required_if:payment_modality,installments|nullable|integer|min:1|max:120",
            "first_due_date" => "required_if:payment_modality,installments|nullable|date|after_or_equal:issue_date",
            "observation" => "nullable|string|max:300",
            "taxes" => "nullable|array|max:20",
            "taxes.*.tax_id" => "required_with:taxes|integer",
            "taxes.*.rate" => "nullable|numeric|min:0|max:$maxValue|decimal:0,$round",
            "taxes.*.calculation_type" => "nullable|in:percentage,fixed",
            "taxes.*.operation_type" => "nullable|in:addition,subtraction",
            "taxes.*.is_required" => "nullable|boolean",
            "taxes.*.quantity" => "nullable|integer|min:1|max:$maxValue",
            "taxes.*.amount" => "nullable|numeric|min:-$maxValue|max:$maxValue|decimal:0,$round",
            "payments" => "nullable|array|max:20",
            "payments.*.payment_method_id" => "required_with:payments|integer",
            "payments.*.payment_method_variant_id" => "nullable|integer",
            "payments.*.amount" => "required_with:payments|numeric|min:0.01|max:$maxValue|decimal:0,$round",
            "payments.*.reference" => "nullable|string|max:100",
            "payments.*.note" => "nullable|string|max:300",
            // Details
            "details" => "required|array",
            "details.*.item_id" => "required|integer",
            "details.*.customer_id" => ["nullable", "integer", new ExistsInTenant("customers", ["status" => "active"], "El cliente beneficiario de la membresía no pertenece a la empresa.")],
            "details.*.type" => "required|string|max:255",
            "details.*.currency_id" => ["required", "integer", new ExistsInTenant("currencies", ["status" => "active"], "Una moneda del detalle no pertenece a la empresa.")],
            "details.*.name" => "required|string|max:255",
            "details.*.quantity" => "required|numeric|min:0.1|max:$maxValue|decimal:0,$round",
            "details.*.price" => "required|numeric|min:0.1|max:$maxValue|decimal:0,$round",
            "details.*.total" => "nullable|numeric|min:0|max:$maxValue|decimal:0,$round",
            "details.*.price_includes_tax" => "nullable|boolean",
            "details.*.igv_exempt" => "nullable|boolean",
            "details.*.commission_type" => "nullable|in:none,percentage,fixed",
            "details.*.commission_value" => "nullable|numeric|min:0|max:$maxValue|decimal:0,$round",
            "details.*.commission_amount" => "nullable|numeric|min:0|max:$maxValue|decimal:0,$round",
            "details.*.observation" => "nullable|string|max:300",
            "details.*.extras" => "nullable|array",
            "details.*.extras.duration_type" => "nullable|in:hour,day,today,month,year",
            "details.*.extras.duration_value" => "nullable|integer|min:1|max:$maxValue",
            "details.*.extras.start_date" => "nullable|date",
            "details.*.extras.end_date" => "nullable|date|after_or_equal:details.*.extras.start_date",
            "details.*.extras.set_end_of_day" => "nullable|boolean",
            "details.*.extras.force" => "nullable|boolean",
            "details.*.extras.observation" => "nullable|string|max:300",
            "details.*.extras.recipe_options" => "nullable|array|max:50",
            "details.*.extras.recipe_options.*.option_id" => "required_with:details.*.extras.recipe_options|integer",
            "details.*.extras.recipe_options.*.portions" => "nullable|integer|min:1|max:100",
            "details.*.extras.recipe_toppings" => "nullable|array|max:50",
            "details.*.extras.recipe_toppings.*.recipe_dish_topping_id" => "required_with:details.*.extras.recipe_toppings|integer",
            "details.*.extras.recipe_toppings.*.quantity" => "required_with:details.*.extras.recipe_toppings|integer|min:0|max:100",
        ];

    }

    public function messages(): array {

        return [
            "details.required" => "Agrega al menos un producto, servicio o membresía.",
            "details.*.quantity.required" => "La cantidad es obligatoria.",
            "details.*.quantity.numeric" => "La cantidad debe ser un número válido.",
            "details.*.quantity.min" => "La cantidad debe ser mayor que cero.",
            "details.*.quantity.max" => "La cantidad supera el máximo permitido.",
            "details.*.price.required" => "El precio es obligatorio.",
            "details.*.price.numeric" => "El precio debe ser un número válido.",
            "details.*.price.min" => "El precio debe ser mayor que cero.",
            "details.*.price.max" => "El precio supera el máximo permitido.",
            "details.*.total.numeric" => "El total debe ser un número válido.",
            "details.*.total.max" => "El total supera el máximo permitido.",
            "payments.*.amount.required_with" => "El monto del método de pago es obligatorio.",
            "payments.*.amount.numeric" => "El monto del método de pago debe ser un número válido.",
            "payments.*.amount.min" => "El monto del método de pago debe ser mayor que cero.",
            "payments.*.amount.max" => "El monto del método de pago supera el máximo permitido.",
            "taxes.*.amount.numeric" => "El importe del tributo debe ser un número válido.",
            "taxes.*.amount.max" => "El importe del tributo supera el máximo permitido.",
            "taxes.*.quantity.integer" => "La cantidad del tributo debe ser un número entero.",
            "taxes.*.quantity.min" => "La cantidad del tributo debe ser al menos 1.",
            "taxes.*.quantity.max" => "La cantidad del tributo supera el máximo permitido.",
        ];

    }

    protected function failedValidation(Validator $validator): void {

        $errors = $validator->errors()->toArray();

        // Props rename for frontend compatibility
        $fieldMappings = [
            "branch_id" => "branch",
            "serie_id" => "serie",
            "warehouse_id" => "warehouse",
            "delivery_method_id" => "delivery_method",
            "holder_id" => "holder",
            "seller_id" => "seller",
            "currency_id" => "currency",
        ];

        $renamedErrors = [];

        foreach($errors as $key => $value) {

            $newKey = $fieldMappings[$key] ?? $key;
            $renamedErrors[$newKey] = $value;

        }

        // Throw exception with renamed errors
        throw new HttpResponseException(
            ApiResponse::validationError($renamedErrors, "Validation failed")
        );

    }

    private function normalizeDetails(): array {

        return collect($this->input("details", []))
            ->map(function($detail) {

                if(!is_array($detail)) {

                    return $detail;

                }

                $detail["item_id"] = $this->nullableIntegerFromArray($detail, "item_id");

                $detail["customer_id"] = $this->nullableIntegerFromArray($detail, "customer_id");
                $detail["currency_id"] = $this->nullableIntegerFromArray($detail, "currency_id");
                $detail["quantity"] = $this->normalizeDecimalFromArray($detail, "quantity");
                $detail["price"] = $this->normalizeDecimalFromArray($detail, "price");
                $detail["total"] = $this->normalizeDecimalFromArray($detail, "total");
                $detail["commission_value"] = $this->normalizeDecimalFromArray($detail, "commission_value");
                $detail["commission_amount"] = $this->normalizeDecimalFromArray($detail, "commission_amount");
                $detail["igv_exempt"] = filter_var($detail["igv_exempt"] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
                $detail["price_includes_tax"] = filter_var($detail["price_includes_tax"] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

                if($detail["igv_exempt"]) {

                    $detail["price_includes_tax"] = false;

                }

                return $detail;

            })
            ->values()
            ->all();

    }

    private function normalizeTaxes(): array {

        return collect($this->input("taxes", []))
            ->map(function($tax) {

                if(!is_array($tax)) {

                    return $tax;

                }

                $tax["tax_id"] = $this->nullableIntegerFromArray($tax, "tax_id");

                $tax["rate"] = $this->normalizeDecimalFromArray($tax, "rate");
                $tax["quantity"] = $this->nullableIntegerFromArray($tax, "quantity");
                $tax["amount"] = $this->normalizeDecimalFromArray($tax, "amount");
                $tax["is_required"] = filter_var($tax["is_required"] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

                return $tax;

            })
            ->values()
            ->all();

    }

    private function normalizePayments(): array {

        return collect($this->input("payments", []))
            ->map(function($payment) {

                if(!is_array($payment)) {

                    return $payment;

                }

                $payment["payment_method_id"] = $this->nullableIntegerFromArray($payment, "payment_method_id");

                $payment["payment_method_variant_id"] = $this->nullableIntegerFromArray($payment, "payment_method_variant_id");
                $payment["amount"] = $this->normalizeDecimalFromArray($payment, "amount");

                return $payment;

            })
            ->values()
            ->all();

    }
}
