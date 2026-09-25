<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\System\Database\{SystemCatalogSyncService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService};
use Database\Seeders\{SystemNavigationSeeder};
use Illuminate\Console\{Command};
use Illuminate\Support\Facades\{Artisan};
use Illuminate\Support\{Str};

final class InstallSystemDatabase extends Command {
    protected $signature = "system:install
        {--slug=demo : Identificador único}
        {--commercial-name=Empresa demo : Nombre comercial}
        {--legal-name=EMPRESA DEMO : Razón social}
        {--document-number=99999999999 : Documento fiscal}
        {--admin-name=Administrador : Nombre del administrador}
        {--admin-email=admin@example.com : Correo del administrador}
        {--admin-password= : Contraseña; obligatoria en modo no interactivo}
        {--fresh : Reconstruye todas las tablas; elimina los datos actuales}
        {--seed : Ejecuta seeders adicionales después del aprovisionamiento}";

    protected $description = "Instala esquema, catálogo y organización inicial en un flujo único e idempotente.";

    public function handle(CompanyProvisioningService $provisioning, SystemCatalogSyncService $catalog): int {

        if($this->option("fresh") && !$this->confirm("Esto eliminará todos los datos. ¿Deseas continuar?", false)) {

            return self::FAILURE;

        }

        $migrationCommand = $this->option("fresh") ? "migrate:fresh" : "migrate";

        $migrationOptions = ["--force" => true];

        if($this->option("seed")) {

            $migrationOptions["--seed"] = true;

        }
        $this->components->task("Aplicando esquema de base de datos", function() use ($migrationCommand, $migrationOptions) {

            return Artisan::call($migrationCommand, $migrationOptions) === self::SUCCESS;

        });
        Artisan::call("db:seed", [
            "--class" => SystemNavigationSeeder::class,
            "--force" => true,
        ]);

        $password = (string) ($this->option("admin-password") ?: "");

        if($password === "" && $this->input->isInteractive()) {

            $password = (string) $this->secret("Contraseña del administrador");

        }

        if(strlen($password) < 8) {

            $this->components->error("La contraseña del administrador debe tener al menos 8 caracteres.");

            return self::FAILURE;

        }

        $slug = Str::slug((string) $this->option("slug"));
        $companyId = $provisioning->createOrUpdate([
            "slug" => $slug,
            "commercial_name" => (string) $this->option("commercial-name"),
            "legal_name" => (string) $this->option("legal-name"),
            "document_number" => (string) $this->option("document-number"),
            "email" => (string) $this->option("admin-email"),
        ]);

        $catalog->sync($companyId);
        $provisioning->enable($companyId);
        $provisioning->ensureAdminUser(
            $companyId,
            (string) $this->option("admin-name"),
            (string) $this->option("admin-email"),
            $password
        );

        Artisan::call("optimize:clear");
        $this->newLine();
        $this->components->info("Sistema listo para {$this->option("commercial-name")} ({$slug}).");

        return self::SUCCESS;

    }
}
