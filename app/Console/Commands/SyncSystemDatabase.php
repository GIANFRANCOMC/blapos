<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\System\Database\{SystemCatalogSyncService};
use Illuminate\Console\{Command};

final class SyncSystemDatabase extends Command {
    protected $signature = "system:sync";

    protected $description = "Proyecta el menú almacenado en la base de datos hacia organizaciones y permisos administrativos.";

    public function handle(SystemCatalogSyncService $service): int {

        $result = $service->sync();
        $this->components->info(sprintf(
            "Menú proyectado para la empresa raíz: %d categorías, %d secciones, %d grupos y %d opciones.",
            $result["categories"], $result["sections"], $result["groups"], $result["items"]
        ));

        return self::SUCCESS;

    }
}
