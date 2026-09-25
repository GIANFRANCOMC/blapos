<?php

declare(strict_types=1);

namespace App\Rules\System\Defaults;

use App\Models\System\General\{IdentityDocumentType};
use Closure;
use Illuminate\Contracts\Validation\{ValidationRule};
use Illuminate\Support\Facades\{Auth};

/**
 * Validation rule to verify that document_number length matches the min_length and max_length of the selected identity_document_type
 */
class DocumentNumberLength implements ValidationRule {
    /**
     * Identity document type ID
     */
    private ?int $identityDocumentTypeId;

    /**
     * Custom attribute name for error messages
     */
    private ?string $attributeName;

    /**
     * Constructor
     *
     * @param  int|null  $identityDocumentTypeId Identity document type ID (if null, will be obtained from request data)
     * @param  string|null  $attributeName Custom attribute name for error messages
     */
    public function __construct(?int $identityDocumentTypeId = null, ?string $attributeName = null) {

        $this->identityDocumentTypeId = $identityDocumentTypeId;
        $this->attributeName = $attributeName;

    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void {

        $identityDocumentTypeId = $this->identityDocumentTypeId;

        if(!$identityDocumentTypeId) {

            $fieldName = $this->attributeName ?? $attribute;
            $fail("El campo {$fieldName} requiere un tipo de documento válido.");

            return;

        }

        $identityDocumentType = IdentityDocumentType::query()
            ->whereKey($identityDocumentTypeId)

            ->first();

        if(!$identityDocumentType) {

            $fieldName = $this->attributeName ?? $attribute;
            $fail("El tipo de documento seleccionado no es válido.");

            return;

        }

        $documentNumber = (string) $value;

        // Validate that document_number contains only numbers

        if(!ctype_digit($documentNumber)) {

            $fieldName = $this->attributeName ?? $attribute;
            $fail("El campo {$fieldName} debe contener solo números.");

            return;

        }

        $length = strlen($documentNumber);

        $minLength = (int) ($identityDocumentType->min_length ?? 1);
        $maxLength = (int) ($identityDocumentType->max_length ?? 1);

        if($length < $minLength) {

            $fieldName = $this->attributeName ?? $attribute;
            $fail("El campo {$fieldName} debe tener al menos {$minLength} caracteres.");

            return;

        }

        if($length > $maxLength) {

            $fieldName = $this->attributeName ?? $attribute;
            $fail("El campo {$fieldName} no debe exceder {$maxLength} caracteres.");

            return;

        }

    }
}
