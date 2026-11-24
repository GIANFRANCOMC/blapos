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

    protected $signature = "tenant:navigation-sync {slug? : Tenant específico; omitir para sincronizar todos}
        {--apply-layout : Aplica la distribución vigente al tenant indicado}";

    protected $description = "Completa los registros de navegación y aplica la distribución vigente cuando se solicita.";

    public function handle(
        TenantConnectionManager $connections,
        SystemNavigationSeeder $navigation,
        SystemCatalogSyncService $catalog
    ): int {

        $slug = $this->argument("slug");
        $applyLayout = $this->option("apply-layout");
        if($applyLayout && !$slug) {

            $this->error("Indica el tenant al que quieres aplicar la distribución.");

            return self::FAILURE;

        }
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
                if($applyLayout) {
                    $catalog->applyLayout(SystemNavigationSeeder::layout());
                }else {
                    $catalog->sync();
                }
                $this->info("Navegación verificada: {$tenant->slug}.");

            }catch(Throwable $exception) {

                $hasFailure = true;
                $this->error("No se pudo verificar {$tenant->slug}: {$exception->getMessage()}");

            }finally {

                $connections->disconnect();

            }

        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;

    }

}
