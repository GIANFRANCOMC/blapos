<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Notifications\{NotificationService};
use App\Services\System\Tenancy\{TenantAdministrationService, TenantConnectionManager};
use Illuminate\Console\{Command};
use Throwable;

final class SendPendingSubscriptionEmails extends Command {
    protected $signature = "notifications:send-subscriptions
                            {--tenant= : Procesar únicamente el slug tenant indicado}
                            {--limit=100 : Máximo de notificaciones por ejecución}";

    protected $description = "Envía notificaciones pendientes con contexto tenant y reintentos controlados";

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
                $summary = NotificationService::sendSubscriptionEmails(
                    (int) $this->option("limit")
                );

                $rows[] = [$tenant->slug, $summary["processed"], $summary["sent"], $summary["failed"], "OK"];
                $administration->audit($tenant, "scheduled_notifications", "success", $summary, "scheduler");

            }catch(Throwable $exception) {

                $hasFailure = true;
                $rows[] = [$tenant->slug, 0, 0, 0, $exception->getMessage()];
                $administration->audit($tenant, "scheduled_notifications", "failure", [
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

        $this->table(
            ["Tenant", "Procesadas", "Enviadas", "Fallidas", "Resultado"],
            $rows
        );

        return $hasFailure ? self::FAILURE : self::SUCCESS;

    }
}
