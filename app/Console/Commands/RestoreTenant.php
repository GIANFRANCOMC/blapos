<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantBackupService};
use Illuminate\Console\{Command};
use Throwable;

final class RestoreTenant extends Command {
    protected $signature = "tenant:restore
        {slug : Slug del tenant}
        {backup : Nombre del archivo .sql dentro del tenant}
        {--force : Confirma la restauración destructiva}";

    protected $description = "Restaura de forma controlada la base de datos de un tenant.";

    public function handle(TenantBackupService $backups): int {

        if(!$this->option("force")) {

            $this->error("La restauración requiere --force.");

            return self::FAILURE;

        }

        $tenant = TenantDatabase::query()->where("slug", (string) $this->argument("slug"))->first();

        if(!$tenant) {

            $this->error("El tenant solicitado no existe.");

            return self::FAILURE;

        }

        try {

            $backups->restore(
                $tenant,
                (string) $this->argument("backup"),
                get_current_user() ?: "console"
            );

            $this->info("Tenant restaurado y verificado correctamente.");

            return self::SUCCESS;

        }catch(Throwable $exception) {

            $this->error($exception->getMessage());

            return self::FAILURE;

        }

    }
}
