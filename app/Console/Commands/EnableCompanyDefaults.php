<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\System\Database\{SystemCatalogSyncService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService};
use Illuminate\Console\{Command};

final class EnableCompanyDefaults extends Command {
    protected $signature = "company:enable {--skip-modules : No habilita módulos ni permisos}";

    protected $description = "Aprovisiona de forma idempotente los datos base de una organización.";

    public function handle(CompanyProvisioningService $provisioning, SystemCatalogSyncService $catalog): int {

        $catalog->sync();
        $provisioning->enable(!$this->option("skip-modules"));
        $this->components->info("Empresa del tenant aprovisionada correctamente.");

        return self::SUCCESS;

    }
}
