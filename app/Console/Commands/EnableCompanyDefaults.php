<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\System\Database\{SystemCatalogSyncService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Console\{Command};

final class EnableCompanyDefaults extends Command {
    protected $signature = "company:enable {--skip-modules : No habilita módulos ni permisos}";

    protected $description = "Aprovisiona de forma idempotente los datos base de una organización.";

    public function handle(CompanyProvisioningService $provisioning, SystemCatalogSyncService $catalog): int {

        $companyId = app(TenantCompanyContext::class)->id();
        $catalog->sync();
        $provisioning->enable($companyId, !$this->option("skip-modules"));
        $this->components->info("Empresa del tenant aprovisionada correctamente.");

        return self::SUCCESS;

    }
}
