<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Enums\System\Tenancy\{TenantStatus};
use App\Models\System\Tenancy\{TenantAuditLog, TenantDatabase};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{Cache, DB, Schema};
use Illuminate\Support\{Collection};
use RuntimeException;
use Throwable;

final class TenantAdministrationService {
    public function __construct(private readonly TenantConnectionManager $connectionManager) {
    }

    public function list(?string $status = null): Collection {

        return TenantDatabase::query()
            ->with("domains")
            ->when($status, fn($query) => $query->where("status", $status))
            ->orderBy("slug")
            ->get();

    }

    public function paginate(string $search = "", ?string $status = null, int $perPage = 20): LengthAwarePaginator {

        return TenantDatabase::query()
            ->select([
                "id", "public_id", "slug", "database_name", "status",
                "last_resolved_at", "status_reason", "status_changed_at", "created_at", "updated_at",
            ])
            ->with(["domains" => fn($query) => $query
                ->select(["id", "tenant_database_id", "domain", "is_primary", "status"])
                ->orderByDesc("is_primary")
                ->orderBy("domain")])
            ->when($status, fn($query) => $query->where("status", $status))
            ->when($search !== "", function($query) use ($search): void {

                $prefix = addcslashes($search, "\\%_")."%";
                $query->where(function($query) use ($prefix): void {

                    $query->where("slug", "like", $prefix)
                        ->orWhere("database_name", "like", $prefix)
                        ->orWhereHas("domains", fn($domains) => $domains->where("domain", "like", $prefix));

                });

            })
            ->orderBy("slug")
            ->paginate(min(50, max(10, $perPage)))
            ->withQueryString();

    }

    public function counts(): array {

        $counts = TenantDatabase::query()
            ->selectRaw("status, COUNT(*) as aggregate")
            ->groupBy("status")
            ->pluck("aggregate", "status")
            ->map(fn($count) => (int) $count)
            ->all();

        return [
            "total" => array_sum($counts),
            "active" => $counts["active"] ?? 0,
            "inactive" => $counts["inactive"] ?? 0,
            "suspended" => $counts["suspended"] ?? 0,
            "provisioning" => $counts["provisioning"] ?? 0,
            "provisioning_failed" => $counts["provisioning_failed"] ?? 0,
            "maintenance" => $counts["maintenance"] ?? 0,
        ];

    }

    public function serialize(TenantDatabase $tenant): array {

        $tenant->loadMissing("domains");
        $primaryDomain = $tenant->domains->firstWhere("is_primary", true)
            ?? $tenant->domains->first();

        return [
            "id" => (string) $tenant->public_id,
            "slug" => (string) $tenant->slug,
            "database_name" => (string) $tenant->database_name,
            "status" => (string) $tenant->status,
            "status_reason" => $tenant->status_reason,
            "status_changed_at" => $tenant->status_changed_at?->toIso8601String(),
            "domain" => $primaryDomain?->domain,
            "url" => $primaryDomain ? "//{$primaryDomain->domain}" : null,
            "last_resolved_at" => $tenant->last_resolved_at?->toIso8601String(),
            "created_at" => $tenant->created_at?->toIso8601String(),
            "updated_at" => $tenant->updated_at?->toIso8601String(),
        ];

    }

    public function health(TenantDatabase $tenant): array {

        $startedAt = microtime(true);

        try {

            $this->connectionManager->connect($tenant);
            DB::connection((string) config("tenancy.tenant_connection", "tenant"))->selectOne("SELECT 1");
            $hasCompanies = Schema::connection((string) config("tenancy.tenant_connection", "tenant"))
                ->hasTable("companies");

            $result = [
                "healthy" => $hasCompanies,
                "database" => $tenant->database_name,
                "latency_ms" => round((microtime(true) - $startedAt) * 1000, 2),
                "message" => $hasCompanies
                    ? "Conexión disponible y esquema base presente."
                    : "La conexión responde, pero falta la tabla companies.",
            ];

            $this->audit($tenant, "health_check", $hasCompanies ? "success" : "failure", $result);

            return $result;

        }catch(Throwable $exception) {

            $result = [
                "healthy" => false,
                "database" => $tenant->database_name,
                "latency_ms" => round((microtime(true) - $startedAt) * 1000, 2),
                "message" => $exception->getMessage(),
            ];

            $this->audit($tenant, "health_check", "failure", $result);

            return $result;

        }finally {

            $this->connectionManager->disconnect();

        }

    }

    public function changeStatus(
        TenantDatabase $tenant,
        string $status,
        ?string $actor = null,
        ?string $reason = null
    ): TenantDatabase {

        if(!in_array($status, TenantStatus::manuallyAssignableValues(), true)) {

            throw new RuntimeException("El estado solicitado no es válido para un tenant.");

        }

        $previousStatus = $tenant->status;

        $tenant->forceFill([
            "status" => $status,
            "status_reason" => $reason,
            "status_changed_at" => now(),
            "updated_at" => now(),
        ])->save();
        $this->clearResolverCache($tenant);
        $this->audit($tenant, "status_changed", "success", [
            "previous_status" => $previousStatus,
            "new_status" => $status,
            "reason" => $reason,
        ], $actor);

        return $tenant->fresh("domains");

    }

    public function clearResolverCache(?TenantDatabase $tenant = null, ?string $actor = null): int {

        $tenants = $tenant ? collect([$tenant->loadMissing("domains")]) : $this->list();
        $cleared = 0;

        foreach($tenants as $currentTenant) {

            foreach($currentTenant->domains as $domain) {

                if(Cache::forget(TenantResolver::cacheKey((string) $domain->domain))) {

                    $cleared++;

                }

            }
            $this->audit($currentTenant, "resolver_cache_cleared", "success", [
                "domains" => $currentTenant->domains->pluck("domain")->values()->all(),
            ], $actor);

        }

        return $cleared;

    }

    public function audit(
        ?TenantDatabase $tenant,
        string $action,
        string $result,
        array $context = [],
        ?string $actor = null,
        ?string $host = null,
        ?string $ipAddress = null
    ): void {

        try {

            if(!Schema::connection((string) config("tenancy.landlord_connection", "landlord"))->hasTable("tenant_audit_logs")) {

                return;

            }

            TenantAuditLog::query()->create([
                "tenant_database_id" => $tenant?->id,
                "action" => $action,
                "result" => $result,
                "host" => $host,
                "ip_address" => $ipAddress,
                "actor" => $actor,
                "context" => $context === [] ? null : $context,
                "occurred_at" => now(),
            ]);

        }catch(Throwable) {
            // La auditoría no debe convertir un rechazo seguro o un comando operativo en un error 500.
        }

    }
}
