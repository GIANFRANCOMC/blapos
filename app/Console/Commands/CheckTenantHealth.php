<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantAdministrationService};
use Illuminate\Console\{Command};

final class CheckTenantHealth extends Command {

    protected $signature = "tenant:health {slug? : Tenant específico; omitir para revisar todos}";

    protected $description = "Comprueba conexión y esquema base de uno o todos los tenants.";

    public function handle(TenantAdministrationService $service): int {

        $slug = $this->argument("slug");
        $tenants = $slug
            ? TenantDatabase::query()->where("slug", $slug)->get()
            : $service->list();

        if($tenants->isEmpty()) {

            $this->error("No se encontró el tenant solicitado.");

            return self::FAILURE;

        }

        $hasFailure = false;

        $rows = $tenants->map(function($tenant) use ($service, &$hasFailure) {

            $health = $service->health($tenant);
            $hasFailure = $hasFailure || !$health["healthy"];

            return [$tenant->slug, $tenant->status, $health["database"], $health["healthy"] ? "OK" : "ERROR", $health["latency_ms"], $health["message"]];

        });

        $this->table(["Tenant", "Estado", "Base", "Salud", "ms", "Detalle"], $rows->all());

        return $hasFailure ? self::FAILURE : self::SUCCESS;

    }

}
