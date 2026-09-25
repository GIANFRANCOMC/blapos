<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\System\Tenancy\{TenantAdministrationService};
use Illuminate\Console\{Command};

final class ListTenants extends Command {
    protected $signature = "tenant:list {--status= : Filtra por estado operativo del tenant}";

    protected $description = "Lista tenants, dominios y bases registradas en landlord.";

    public function handle(TenantAdministrationService $service): int {

        $records = $service->list($this->option("status") ?: null);
        $this->table(["ID", "Slug", "Dominio", "Base", "Estado", "Última resolución"], $records->map(fn($tenant) => [
            $tenant->id,
            $tenant->slug,
            $tenant->domains->firstWhere("is_primary", true)?->domain ?? $tenant->domains->first()?->domain,
            $tenant->database_name,
            $tenant->status,
            $tenant->last_resolved_at?->format("Y-m-d H:i:s"),
        ])->all());

        return self::SUCCESS;

    }
}
