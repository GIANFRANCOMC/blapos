<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Concerns;

use App\Services\System\Base\{InternalCodeService};

trait AppliesInternalCodePrefix {
    protected function prepareForValidation(): void {

        parent::prepareForValidation();

        if(!$this->exists("internal_code")) {

            return;

        }

        $this->merge([
            "internal_code" => InternalCodeService::applyPrefix(
                $this->companyId(),
                $this->internalCodeEntity(),
                $this->input("internal_code")
            ),
        ]);

    }

    abstract protected function internalCodeEntity(): string;
}
