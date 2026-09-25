<?php

declare(strict_types=1);

namespace App\Rules\System\Defaults;

use Closure;
use Illuminate\Contracts\Validation\{ValidationRule};
use Illuminate\Support\Facades\{DB};

/**
 * Verifies that a value is unique in the database of the current tenant.
 */
class UniqueInTenant implements ValidationRule {
    /**
     * @param  array<string, mixed>  $extraWhere
     */
    public function __construct(
        private readonly string $table,
        private readonly string $field,
        private readonly ?int $excludeId = null,
        private readonly array $extraWhere = [],
        private readonly ?string $attributeName = null
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void {

        $query = DB::table($this->table)
            ->where($this->field, $value);

        foreach($this->extraWhere as $field => $extraValue) {

            $query->where((string) $field, $extraValue);

        }

        if($this->excludeId !== null) {

            $query->where("id", "!=", $this->excludeId);

        }

        if($query->exists()) {

            $fieldName = $this->attributeName ?? $attribute;
            $fail("El campo {$fieldName} ya está en uso.");

        }

    }
}
