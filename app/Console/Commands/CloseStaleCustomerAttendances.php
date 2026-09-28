<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Customers\Tracking\{AttendanceMaintenanceService};
use App\Services\System\Tenancy\{TenantAdministrationService, TenantConnectionManager};
use Illuminate\Console\{Command};
use Throwable;

final class CloseStaleCustomerAttendances extends Command {
    protected $signature = "attendances:close-stale-customers
                            {--tenant= : Procesar únicamente el slug tenant indicado}
                            {--limit=500 : Máximo de asistencias por tenant}
                            {--force : Ejecuta aunque no haya llegado la hora configurada}";

    protected $description = "Cierra asistencias de clientes que quedaron abiertas sin salida";

    public function handle(
        TenantConnectionManager $connectionManager,
        TenantAdministrationService $administration
    ): int {

        $tenantSlug = $this->option("tenant");
        $tenants = TenantDatabase::query()
            ->where("status", "active")
            ->when($tenantSlug, fn($query) => $query->where("slug", $tenantSlug))
            ->lazyById(100);

        $rows = [];
        $hasFailure = false;
        $processedTenants = 0;

        foreach($tenants as $tenant) {

            $processedTenants++;

            try {

                $connectionManager->connect($tenant);
                $summary = AttendanceMaintenanceService::closeStaleCustomerAttendances(
                    max(1, (int) $this->option("limit")),
                    (bool) $this->option("force")
                );

                $rows[] = [$tenant->slug, $summary["closed"], $summary["skipped"], "OK"];
                $administration->audit($tenant, "close_stale_customer_attendances", "success", $summary, "scheduler");

            }catch(Throwable $exception) {

                $hasFailure = true;
                $rows[] = [$tenant->slug, 0, 0, $exception->getMessage()];
                $administration->audit($tenant, "close_stale_customer_attendances", "failure", [
                    "error" => $exception->getMessage(),
                ], "scheduler");

            }finally {

                $connectionManager->disconnect();

            }

        }

        if($processedTenants === 0) {

            $this->error("No existen tenants activos para procesar.");

            return self::FAILURE;

        }

        $this->table(["Tenant", "Cerradas", "Omitidas", "Resultado"], $rows);

        return $hasFailure ? self::FAILURE : self::SUCCESS;

    }
}
