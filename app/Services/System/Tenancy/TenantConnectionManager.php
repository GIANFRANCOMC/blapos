<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Models\System\Tenancy\{TenantDatabase};
use Illuminate\Support\Facades\{Config, DB};
use RuntimeException;

final class TenantConnectionManager {
    public function connect(TenantDatabase $tenant): void {

        app(TenantCompanyContext::class)->forget();
        app(TenantContext::class)->set($tenant);

        $connectionName = config("tenancy.tenant_connection", "tenant");
        $base = config("database.connections.{$connectionName}", config("database.connections.mysql"));
        $databaseName = $tenant->database_name;
        $databasePrefix = (string) config("tenancy.database_prefix", "blapos_tenant_");

        if(!preg_match("/^[a-zA-Z0-9_]+\$/", $databaseName)) {

            throw new RuntimeException("La base de datos tenant configurada no es válida.");

        }

        if(config("tenancy.enforce_database_prefix", true) && !str_starts_with($databaseName, $databasePrefix)) {

            throw new RuntimeException("La base de datos tenant no cumple el prefijo permitido.");

        }

        $base["database"] = $databaseName;

        Config::set("database.connections.{$connectionName}", $base);
        Config::set("database.default", $connectionName);

        DB::purge($connectionName);
        DB::setDefaultConnection($connectionName);
        DB::reconnect($connectionName);

    }

    public function disconnect(): void {

        app(TenantCompanyContext::class)->forget();
        app(TenantContext::class)->set(null);

        $connectionName = config("tenancy.tenant_connection", "tenant");
        $landlordConnection = config("tenancy.landlord_connection", "landlord");

        DB::purge($connectionName);
        Config::set("database.default", $landlordConnection);
        DB::setDefaultConnection($landlordConnection);

    }
}
