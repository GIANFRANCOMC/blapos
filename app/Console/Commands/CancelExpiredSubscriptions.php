<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Support\{TenantCommandBatchLimit, TenantCommandReport};
use App\Events\{SubscriptionExpired};
use App\Models\System\Customers\{Subscription};
use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantAdministrationService, TenantConnectionManager};
use Illuminate\Console\{Command};
use Throwable;

final class CancelExpiredSubscriptions extends Command {
    protected $signature = "subscriptions:cancel-expired
                            {--tenant= : Procesar únicamente el slug tenant indicado}
                            {--limit=1000 : Máximo de membresías por tenant}";

    protected $description = "Inactiva membresías vencidas con contexto tenant";

    public function handle(
        TenantConnectionManager $connectionManager,
        TenantAdministrationService $administration
    ): int {

        $tenantSlug = $this->option("tenant");
        $tenants = TenantDatabase::query()
            ->where("status", "active")
            ->when($tenantSlug, fn($query) => $query->where("slug", $tenantSlug))
            ->lazyById(100);

        $report = new TenantCommandReport($this, ["Tenant", "Procesadas", "Vencidas", "Resultado"]);
        $hasFailure = false;
        $processedTenants = 0;

        foreach($tenants as $tenant) {

            $processedTenants++;

            try {

                $connectionManager->connect($tenant);
                $summary = $this->expireSubscriptions(TenantCommandBatchLimit::normalize($this->option("limit")));

                $report->add([$tenant->slug, $summary["processed"], $summary["expired"], "OK"]);
                $administration->audit($tenant, "cancel_expired_subscriptions", "success", $summary, "scheduler");

            }catch(Throwable $exception) {

                $hasFailure = true;
                $report->add([$tenant->slug, 0, 0, $exception->getMessage()]);
                $administration->audit($tenant, "cancel_expired_subscriptions", "failure", [
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

    private function expireSubscriptions(int $limit): array {

        $subscriptions = Subscription::query()
            ->where("status", "active")

            ->where("end_date", "<=", now())
            ->orderBy("end_date")
            ->limit($limit)
            ->get();

        foreach($subscriptions as $subscription) {

            $subscription->update([
                "motive" => "Membresía expirada.",
                "status" => "inactive",
                "updated_at" => now(),
                "updated_by" => null,
            ]);

            event(new SubscriptionExpired($subscription));

        }

        return [
            "processed" => $subscriptions->count(),
            "expired" => $subscriptions->count(),
        ];

    }
}
