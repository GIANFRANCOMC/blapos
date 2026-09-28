<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Support\{TenantCommandBatchLimit, TenantCommandReport};
use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Customers\Tracking\{AttendanceMaintenanceService};
use App\Services\System\Tenancy\{TenantAdministrationService, TenantConnectionManager};
use Illuminate\Console\{Command};
use Throwable;

final class PruneCustomerAttendances extends Command {
    protected $signature = "attendances:prune-customers
                            {--tenant= : Procesar únicamente el slug tenant indicado}
                            {--months= : Meses de retención; mínimo 4}
                            {--limit=1000 : Máximo de asistencias por tenant}
                            {--dry-run : Solo cuenta registros elegibles}";

    protected $description = "Depura asistencias antiguas de clientes respetando la retención configurada";

    public function handle(
        TenantConnectionManager $connectionManager,
        TenantAdministrationService $administration
    ): int {

        $tenantSlug = $this->option("tenant");
        $months = $this->option("months") === null ? null : max(4, (int) $this->option("months"));
        $tenants = TenantDatabase::query()
            ->where("status", "active")
            ->when($tenantSlug, fn($query) => $query->where("slug", $tenantSlug))
            ->lazyById(100);

        $report = new TenantCommandReport($this, ["Tenant", "Elegibles", "Eliminadas", "Resultado"]);
        $hasFailure = false;
        $processedTenants = 0;

        foreach($tenants as $tenant) {

            $processedTenants++;

            try {

                $connectionManager->connect($tenant);
                $summary = AttendanceMaintenanceService::pruneCustomerAttendances(
                    $months,
                    TenantCommandBatchLimit::normalize($this->option("limit")),
                    (bool) $this->option("dry-run")
                );

                $report->add([$tenant->slug, $summary["eligible"], $summary["deleted"], "OK"]);
                $administration->audit($tenant, "prune_customer_attendances", "success", $summary, "scheduler");

            }catch(Throwable $exception) {

                $hasFailure = true;
                $report->add([$tenant->slug, 0, 0, $exception->getMessage()]);
                $administration->audit($tenant, "prune_customer_attendances", "failure", [
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

        $report->flush();

        return $hasFailure ? self::FAILURE : self::SUCCESS;

    }
}
