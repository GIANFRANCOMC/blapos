<?php

declare(strict_types=1);

namespace App\Rules\System\Catalogs;

use Closure;
use Illuminate\Contracts\Validation\{ValidationRule};

final class DelimitedArrayTextLength implements ValidationRule {

    public function __construct(
        private readonly int $maxLength,
        private readonly string $label,
        private readonly string $delimiter = ", "
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void {

        if(!is_array($value)) {

            return;

        }

        foreach($value as $item) {

            if(!is_string($item)) {

                return;

            }

        }

        $items = array_values(array_filter(array_map(trim(...), $value), fn(string $item) => $item !== ""));

        if(mb_strlen(implode($this->delimiter, $items)) > $this->maxLength) {

            $fail("El campo {$this->label} no puede superar {$this->maxLength} caracteres en total.");

        }

    }

}
