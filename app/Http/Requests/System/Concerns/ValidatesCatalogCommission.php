<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Concerns;

use App\Helpers\System\{Numbers};
use Illuminate\Validation\{Validator};

trait ValidatesCatalogCommission {

    protected function normalizedCommission(): array {

        $type = $this->input("commission_type") ?: "none";

        return [
            "commission_type" => $type,
            "commission_value" => $type === "none" ? 0 : $this->normalizeDecimalInput($this->input("commission_value")),
        ];

    }

    protected function validateCommission(Validator $validator): void {

        $type = $this->input("commission_type");
        if(!in_array($type, ["percentage", "fixed"], true) || $validator->errors()->hasAny(["commission_type", "commission_value"])) {
            return;
        }

        $value = $this->input("commission_value");
        if($value === null || Numbers::compare($value, 0) <= 0) {
            $validator->errors()->add("commission_value", "Ingresa un valor de comisión mayor que 0.");
        }elseif($type === "percentage" && Numbers::compare($value, 100) > 0) {
            $validator->errors()->add("commission_value", "No puede superar el 100%.");
        }

    }

}
