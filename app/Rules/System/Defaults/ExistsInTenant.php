<?php

declare(strict_types=1);

namespace App\Rules\System\Defaults;

use Closure;
use Illuminate\Contracts\Validation\{ValidationRule};
use Illuminate\Support\Facades\{DB};

/**
 * Verifies that a record exists in the database of the current tenant.
 */
class ExistsInTenant implements ValidationRule {
    /**
     * @param  array<string, mixed>  $extraWhere
     * @param  array<int, array{0:string, 1:string, 2:string, 3:string}>  $joins
     */
    public function __construct(
        private readonly string $table,
        private readonly array $extraWhere = [],
        private readonly ?string $customMessage = null,
        private readonly array $joins = [],
        private readonly string $keyColumn = "id"
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void {

        $query = DB::table($this->table);

        foreach($this->joins as $join) {

            if(count($join) !== 4) {

                throw new \InvalidArgumentException("Tenant existence joins require table, first column, operator and second column.");

            }

            $query->join($join[0], $join[1], $join[2], $join[3]);

        }

        $query->where($this->keyColumn, $value);

        foreach($this->extraWhere as $field => $extraValue) {

            $query->where((string) $field, $extraValue);

        }

        if(!$query->exists()) {

            $fail($this->customMessage ?? "El registro seleccionado no está disponible.");

        }

    }
}
