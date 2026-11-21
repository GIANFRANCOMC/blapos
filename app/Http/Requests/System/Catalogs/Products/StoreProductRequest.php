<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Products;

class StoreProductRequest extends ProductRequest {

    public function rules(): array {

        return [...parent::rules(), "expires_at" => ["nullable", "date", "after_or_equal:".today()->toDateString()]];

    }

    public function messages(): array {

        return [...parent::messages(), "expires_at.after_or_equal" => "Elige hoy o una fecha futura."];

    }
}
