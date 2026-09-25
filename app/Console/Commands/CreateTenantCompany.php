<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\System\Tenancy\{TenantStatus};
use App\Models\System\Tenancy\{TenantDatabase, TenantDomain};
use App\Services\System\Database\{SystemCatalogSyncService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService};
use App\Services\System\Tenancy\{LandlordSchemaService, TenantConnectionManager};
use Database\Seeders\{SystemNavigationSeeder};
use Illuminate\Console\{Command};
use Illuminate\Support\Facades\{Artisan, Cache, DB};
use Illuminate\Support\{Str};
use InvalidArgumentException;
use Throwable;

final class CreateTenantCompany extends Command {
    protected $signature = "tenant:create
        {slug : Subdominio/código único del cliente}
        {--domain= : Subdominio completo. Debe coincidir con slug + TENANCY_BASE_DOMAIN}
        {--database= : Nombre de la base de datos tenant. Si se omite usa TENANT_DB_PREFIX + slug}
        {--commercial-name= : Nombre comercial inicial}
        {--legal-name= : Razón social inicial}
        {--document-number=99999999999 : Documento inicial}
        {--admin-name=Administrador : Nombre del administrador inicial}
        {--admin-email=admin@example.com : Correo del administrador inicial}
        {--admin-password= : Contraseña del administrador inicial}
        {--force : Actualiza el registro landlord si ya existe}
        {--skip-create-database : Usa una base creada previamente por infraestructura}
        {--skip-migrate : Solo crea DB y registry, sin ejecutar migraciones tenant}
        {--skip-cache-clear : No ejecuta optimize:clear al final}";

    protected $description = "Crea una compañía tenant con base de datos aislada y subdominio registrado.";

    public function handle(
        TenantConnectionManager $connectionManager,
        CompanyProvisioningService $provisioning,
        SystemCatalogSyncService $catalog
    ): int {

        $slug = $this->normalizeSlug((string) $this->argument("slug"));
        $databaseName = $this->normalizeDatabaseName((string) ($this->option("database") ?: config("tenancy.database_prefix", "blapos_tenant_").$slug));
        $domain = $this->normalizeDomain((string) ($this->option("domain") ?: $this->defaultDomain($slug)));

        $this->assertSubdomainIsAllowed($slug, $domain);

        LandlordSchemaService::ensure();

        $this->assertRegistryIsAvailable($slug, $domain, $databaseName);

        $tenant = null;

        try {

            if(!$this->option("skip-create-database")) {

                $this->createDatabase($databaseName);

            }

            $tenant = $this->upsertTenantRegistry($slug, $databaseName, $domain);

            $connectionManager->connect($tenant);

            if(!$this->option("skip-migrate")) {

                $this->runTenantMigrations();
                Artisan::call("db:seed", [
                    "--class" => SystemNavigationSeeder::class,
                    "--database" => config("tenancy.tenant_connection", "tenant"),
                    "--force" => true,
                ]);

                $companyId = $provisioning->createOrUpdate([
                    "slug" => $slug,
                    "commercial_name" => $this->option("commercial-name") ?: Str::headline($slug),
                    "legal_name" => $this->option("legal-name") ?: Str::upper(Str::headline($slug)),
                    "document_number" => (string) $this->option("document-number"),
                    "email" => (string) $this->option("admin-email"),
                ]);

                $catalog->sync();
                $provisioning->enable($companyId);

                $adminPassword = (string) $this->option("admin-password");

                if($adminPassword === "" && $this->input->isInteractive()) {

                    $adminPassword = (string) $this->secret("Contraseña del administrador inicial");

                }

                if(strlen($adminPassword) < 8) {

                    throw new InvalidArgumentException("admin-password es obligatorio y debe tener al menos 8 caracteres.");

                }
                $provisioning->ensureAdminUser(
                    $companyId,
                    (string) $this->option("admin-name"),
                    (string) $this->option("admin-email"),
                    $adminPassword
                );

            }

            DB::connection("landlord")
                ->table("tenant_databases")
                ->where("id", $tenant->id)
                ->update([
                    "status" => TenantStatus::ACTIVE->value,
                    "status_reason" => null,
                    "status_changed_at" => now(),
                    "updated_at" => now(),
                ]);

            Cache::forget("tenancy:resolver:".hash("sha256", $domain));

            if(!$this->option("skip-cache-clear")) {

                Artisan::call("optimize:clear");

            }

            $this->info("Tenant {$slug} creado correctamente.");

            $this->line("Dominio: {$domain}");
            $this->line("Base de datos: {$databaseName}");

            return self::SUCCESS;

        }catch(Throwable $exception) {

            if($tenant) {

                DB::connection("landlord")
                    ->table("tenant_databases")
                    ->where("id", $tenant->id)
                    ->update([
                        "status" => TenantStatus::PROVISIONING_FAILED->value,
                        "status_reason" => mb_substr($exception->getMessage(), 0, 2000),
                        "status_changed_at" => now(),
                        "updated_at" => now(),
                    ]);

            }

            $this->error($exception->getMessage());

            return self::FAILURE;

        }

    }

    private function normalizeSlug(string $slug): string {

        $slug = Str::slug($slug);

        if($slug === "") {

            throw new InvalidArgumentException("El slug no puede estar vacío.");

        }

        if(strlen($slug) > 63 || !preg_match("/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\$/", $slug)) {

            throw new InvalidArgumentException("El subdominio no tiene un formato válido.");

        }

        if(in_array($slug, config("tenancy.reserved_subdomains", []), true)) {

            throw new InvalidArgumentException("El subdominio está reservado por la plataforma.");

        }

        return $slug;

    }

    private function normalizeDomain(string $domain): string {

        $domain = strtolower(trim($domain));
        $domain = Str::before($domain, ":");

        if($domain === "" || !preg_match("/^[a-z0-9.-]+\$/", $domain)) {

            throw new InvalidArgumentException("El dominio no es válido.");

        }

        return $domain;

    }

    private function normalizeDatabaseName(string $database): string {

        $database = strtolower(trim($database));
        $database = preg_replace("/[^a-z0-9_]/", "_", $database) ?: "";

        if($database === "") {

            throw new InvalidArgumentException("El nombre de base de datos no es válido.");

        }

        $prefix = (string) config("tenancy.database_prefix", "blapos_tenant_");

        if(config("tenancy.enforce_database_prefix", true) && !str_starts_with($database, $prefix)) {

            throw new InvalidArgumentException("La base de datos debe comenzar con {$prefix}.");

        }

        return $database;

    }

    private function defaultDomain(string $slug): string {

        $baseDomain = trim((string) config("tenancy.base_domain"));

        return $baseDomain !== "" ? "{$slug}.{$baseDomain}" : $slug;

    }

    private function assertSubdomainIsAllowed(string $slug, string $domain): void {

        $expectedDomain = $this->defaultDomain($slug);

        if($domain !== $expectedDomain) {

            throw new InvalidArgumentException(
                "Solo se permiten subdominios del dominio base. Use {$expectedDomain}."
            );

        }

    }

    private function assertRegistryIsAvailable(string $slug, string $domain, string $databaseName): void {

        $existingTenant = DB::connection("landlord")
            ->table("tenant_databases")
            ->where("slug", $slug)
            ->first();

        if($existingTenant && !$this->option("force")) {

            throw new InvalidArgumentException("Ya existe un tenant con ese subdominio/slug.");

        }

        $existingDomain = DB::connection("landlord")
            ->table("tenant_domains")
            ->where("domain", $domain)
            ->first();

        if($existingDomain && (!$existingTenant || (int) $existingDomain->tenant_database_id !== (int) $existingTenant->id)) {

            throw new InvalidArgumentException("Ya existe un tenant con ese dominio.");

        }

        $databaseTenant = DB::connection("landlord")
            ->table("tenant_databases")
            ->where("database_name", $databaseName)
            ->first();

        if($databaseTenant && (!$existingTenant || (int) $databaseTenant->id !== (int) $existingTenant->id)) {

            throw new InvalidArgumentException("La base de datos ya está asignada a otro tenant.");

        }

    }

    private function createDatabase(string $databaseName): void {

        $quoted = "`".str_replace("`", "``", $databaseName)."`";
        DB::connection("landlord")->statement("CREATE DATABASE IF NOT EXISTS {$quoted} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    }

    private function upsertTenantRegistry(string $slug, string $databaseName, string $domain) {

        $tenantPayload = [
            "database_name" => $databaseName,
            "status" => TenantStatus::PROVISIONING->value,
            "status_reason" => null,
            "status_changed_at" => now(),
            "updated_at" => now(),
        ];

        $tenant = TenantDatabase::query()->updateOrCreate(
            ["slug" => $slug],
            $tenantPayload
        );

        TenantDomain::query()->updateOrCreate(
            ["domain" => $domain],
            [
                "tenant_database_id" => $tenant->id,
                "type" => "subdomain",
                "is_primary" => true,
                "status" => "active",
            ]
        );

        return $tenant->refresh();

    }

    private function runTenantMigrations(): void {

        Artisan::call("migrate", [
            "--database" => config("tenancy.tenant_connection", "tenant"),
            "--path" => "database/migrations",
            "--force" => true,
        ]);

        $this->line(Artisan::output());

    }
}
