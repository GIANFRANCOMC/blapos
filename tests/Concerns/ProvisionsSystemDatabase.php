<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Services\System\Database\{SystemCatalogSyncService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService};
use Database\Seeders\{SystemNavigationSeeder};

trait ProvisionsSystemDatabase {
    protected function provisionSystemDatabase(): void {

        app(SystemNavigationSeeder::class)->run();
        $provisioning = app(CompanyProvisioningService::class);
        $provisioning->createOrUpdate([
            "slug" => "tests",
            "commercial_name" => "Empresa de pruebas",
            "legal_name" => "EMPRESA DE PRUEBAS S.A.C.",
            "document_number" => "20999999999",
            "email" => "admin@example.test",
        ], 1);
        app(SystemCatalogSyncService::class)->sync();
        $provisioning->enable(1);
        $provisioning->ensureAdminUser(1, "Administrador de pruebas", "admin@example.test", "password");

    }
}
