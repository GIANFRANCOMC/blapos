<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use Illuminate\Support\Facades\{DB};
use InvalidArgumentException;
use Throwable;

final class LandlordDatabaseProvisioner {
    public function ensureExists(): bool {

        $connection = (string) config("tenancy.landlord_connection", "landlord");
        $landlordConnection = DB::connection($connection);
        $connectionConfig = $landlordConnection->getConfig();

        if(!is_array($connectionConfig) || ($connectionConfig["driver"] ?? null) !== "mysql") {

            throw new InvalidArgumentException("La conexión landlord debe utilizar MySQL.");

        }

        $database = trim((string) ($connectionConfig["database"] ?? ""));

        if($database === "" || !preg_match("/^[A-Za-z0-9_]+$/", $database)) {

            throw new InvalidArgumentException("LANDLORD_DB_DATABASE contiene un nombre inválido.");

        }

        try {

            $landlordConnection->getPdo();

            return false;

        }catch(Throwable) {

            DB::purge($connection);

        }

        $provisioningConnection = "landlord_provisioning";

        $connectionConfig["database"] = null;
        $connectionConfig["url"] = null;

        config(["database.connections.{$provisioningConnection}" => $connectionConfig]);

        try {

            DB::connection($provisioningConnection)->statement(
                "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
            );

        }finally {

            DB::purge($provisioningConnection);

        }

        return true;

    }
}
