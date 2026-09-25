<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Models\System\Organizations\{Company};
use RuntimeException;

final class TenantCompanyContext {
    private ?Company $company = null;

    public function get(): Company {

        if($this->company instanceof Company) {

            return $this->company;

        }

        $company = Company::query()->orderBy("id")->first();

        if(!$company) {

            throw new RuntimeException("El tenant no tiene una empresa aprovisionada.");

        }

        $this->company = $company;

        return $company;

    }

    public function id(): int {

        return (int) $this->get()->getKey();

    }

    public function idOrNull(): ?int {

        try {

            return $this->id();

        }catch(RuntimeException) {

            return null;

        }

    }

    public function forget(): void {

        $this->company = null;

    }
}
