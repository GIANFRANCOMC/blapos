<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\TenantDatabase;
use App\Services\System\Database\SystemCatalogSyncService;
use App\Services\System\Tenancy\TenantConnectionManager;
use Database\Seeders\SystemNavigationSeeder;
use Illuminate\Console\Command;
use Throwable;

final class SyncTenantNavigation extends Command {

    protected $signature = "tenant:navigation-sync {slug? : Tenant específico; omitir para sincronizar todos}";

    protected $description = "Consolida el catálogo de navegación de cada tenant y actualiza su proyección sin recrear datos.";

    public function handle(
        TenantConnectionManager $connections,
        SystemNavigationSeeder $navigation,
        SystemCatalogSyncService $catalog
    ): int {

        $slug = $this->argument("slug");
        $tenants = TenantDatabase::query()
            ->when($slug, fn($query) => $query->where("slug", $slug))
            ->orderBy("slug")
            ->get();

        if($tenants->isEmpty()) {

            $this->error("No se encontró el tenant solicitado.");

            return self::FAILURE;

        }

        $hasFailure = false;

        foreach($tenants as $tenant) {

            try {

                $connections->connect($tenant);
                $navigation->run();
                $catalog->sync();
                $this->info("Navegación actualizada: {$tenant->slug}.");

            }catch(Throwable $exception) {

                $hasFailure = true;
                $this->error("No se pudo actualizar {$tenant->slug}: {$exception->getMessage()}");

            }finally {

                $connections->disconnect();

            }

        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;

    }

}
