<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantAdministrationService};
use Illuminate\Console\{Command};
use Throwable;

final class SuspendTenant extends Command {

    protected $signature = "tenant:suspend {slug} {--activate : Reactiva el tenant en lugar de suspenderlo} {--force : Omite confirmación interactiva}";

    protected $description = "Suspende o reactiva un tenant e invalida su caché de resolución.";

    public function handle(TenantAdministrationService $service): int {

        $slug = (string) $this->argument("slug");
        $status = $this->option("activate") ? "active" : "suspended";

        if(!$this->option("force") && !$this->confirm("¿Confirmas cambiar {$slug} a {$status}?")) {

            $this->warn("Operación cancelada.");

            return self::SUCCESS;

        }

        try {

            $tenant = TenantDatabase::query()->where("slug", $slug)->firstOrFail();
            $tenant = $service->changeStatus($tenant, $status, get_current_user() ?: "console");
            $this->info("Tenant {$tenant->slug} actualizado a {$tenant->status}.");

            return self::SUCCESS;

        }catch(Throwable $exception) {

            $this->error($exception->getMessage());

            return self::FAILURE;

        }

    }

}
