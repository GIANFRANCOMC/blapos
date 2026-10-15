<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\System\Tenancy\{LandlordDatabaseProvisioner};
use Database\Seeders\{LandlordPlatformSeeder};
use Illuminate\Console\{Command};
use Illuminate\Support\Facades\{Artisan};

final class InstallPlatformAdministration extends Command {

    protected $signature = "platform:install";

    protected $description = "Crea y prepara landlord, luego configura el administrador inicial del panel app.";

    public function handle(LandlordDatabaseProvisioner $databaseProvisioner): int {

        if($databaseProvisioner->ensureExists()) {

            $this->components->info("Base landlord creada correctamente.");

        }

        $migrationResult = Artisan::call("migrate", [
            "--database" => (string) config("tenancy.landlord_connection", "landlord"),
            "--path" => "database/migrations/landlord",
            "--force" => true,
        ]);

        if($migrationResult !== self::SUCCESS) {

            $this->components->error("No se pudieron aplicar las migraciones landlord.");

            return self::FAILURE;

        }

        $seedResult = Artisan::call("db:seed", [
            "--database" => (string) config("tenancy.landlord_connection", "landlord"),
            "--class" => LandlordPlatformSeeder::class,
            "--force" => true,
        ]);

        if($seedResult !== self::SUCCESS) {

            $this->components->error("No se pudo crear el administrador inicial.");

            return self::FAILURE;

        }

        $host = config("tenancy.platform_subdomain").".".config("tenancy.base_domain");

        $this->components->info("Panel disponible en http://{$host}");
        $this->line("Usuario: ".config("tenancy.platform_admin.email"));

        if(!app()->environment("production")) {

            $this->line("Contraseña inicial: ".config("tenancy.platform_admin.password"));

        }

        return self::SUCCESS;

    }

}
