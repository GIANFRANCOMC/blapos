<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\{TrustHosts as Middleware};

class TrustHosts extends Middleware {

    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array {

        $baseDomain = strtolower(trim((string) config("tenancy.base_domain")));

        if($baseDomain === "") {

            return [];

        }

        return [
            "^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.".preg_quote($baseDomain, "/")."\$",
        ];

    }

    protected function shouldSpecifyTrustedHosts(): bool {

        return (bool) config("tenancy.enforce_subdomains", true);

    }

}
