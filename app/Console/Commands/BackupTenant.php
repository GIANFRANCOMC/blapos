<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantBackupService};
use Illuminate\Console\{Command};
use Throwable;

final class BackupTenant extends Command {
    protected $signature = "tenant:backup {slug : Slug del tenant}";

    protected $description = "Crea un respaldo aislado de la base de datos de un tenant.";

    public function handle(TenantBackupService $backups): int {

        $tenant = TenantDatabase::query()->where("slug", (string) $this->argument("slug"))->first();

        if(!$tenant) {

            $this->error("El tenant solicitado no existe.");

            return self::FAILURE;

        }

        try {

            $path = $backups->backup($tenant, get_current_user() ?: "console");
            $this->info("Respaldo creado: {$path}");

            return self::SUCCESS;

        }catch(Throwable $exception) {

            $this->error($exception->getMessage());

            return self::FAILURE;

        }

    }
}
