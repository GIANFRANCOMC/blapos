<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantAdministrationService};
use Illuminate\Console\{Command};

final class ClearTenantResolverCache extends Command {

    protected $signature = "tenant:cache-clear {slug? : Tenant específico; omitir para todos}";

    protected $description = "Elimina exclusivamente la caché de resolución de dominios tenant.";

    public function handle(TenantAdministrationService $service): int {

        $slug = $this->argument("slug");
        $tenant = $slug ? TenantDatabase::query()->where("slug", $slug)->first() : null;

        if($slug && !$tenant) {

            $this->error("No se encontró el tenant solicitado.");

            return self::FAILURE;

        }

        $cleared = $service->clearResolverCache($tenant, get_current_user() ?: "console");

        $this->info("Claves de resolución eliminadas: {$cleared}.");

        return self::SUCCESS;

    }

}
