<?php

declare(strict_types=1);

namespace App\Rules\System\Catalogs;

use Closure;
use Illuminate\Contracts\Validation\{ValidationRule};

class ValidEan13 implements ValidationRule {

    public function validate(string $attribute, mixed $value, Closure $fail): void {

        $barcode = (string) $value;

        if(!preg_match("/^\d{13}$/", $barcode)) {

            $fail("El código de barras debe contener exactamente 13 dígitos.");

            return;

        }

        $sum = 0;

        for($index = 0; $index < 12; $index++) {

            $digit = (int) $barcode[$index];
            $sum += $index % 2 === 0 ? $digit : $digit * 3;

        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        if($checkDigit !== (int) $barcode[12]) {

            $fail("El código de barras no tiene un dígito de control válido.");

        }

    }

}
