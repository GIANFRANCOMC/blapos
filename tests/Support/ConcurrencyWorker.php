<?php

declare(strict_types=1);

use App\Models\System\Sales\{SaleHeader};
use App\Services\System\Tenancy\{PlatformTenantProvisioner};
use App\Services\System\Warehouses\Inventory\{InventoryMovementService};
use Illuminate\Contracts\Console\{Kernel};
use Illuminate\Support\Facades\{Artisan, DB};

require dirname(__DIR__, 2)."/vendor/autoload.php";

$app = require dirname(__DIR__, 2)."/bootstrap/app.php";
$app->make(Kernel::class)->bootstrap();

$action = $argv[1] ?? "";
$parameters = array_slice($argv, 2);

try {

    if($action === "provision-lock") {

        Artisan::shouldReceive("call")->never();

        try {

            app(PlatformTenantProvisioner::class)->create([
                "slug" => $parameters[0],
            ]);

        }catch(RuntimeException $exception) {

            if(str_contains($exception->getMessage(), "ya se está aprovisionando")) {

                echo "blocked";
                exit(0);

            }

            throw $exception;

        }

        throw new RuntimeException("El segundo proceso atravesó el bloqueo de aprovisionamiento.");

    }

    $barrier = $parameters[0] ?? "";

    $deadline = microtime(true) + 10;

    while(!is_file($barrier)) {

        if(microtime(true) >= $deadline) {

            throw new RuntimeException("No se abrió la barrera de concurrencia.");

        }

        usleep(10000);

    }

    if($action === "issue-sale") {

        $serieId = (int) $parameters[1];
        $customerId = (int) $parameters[2];
        $sellerId = (int) $parameters[3];
        $currencyId = (int) $parameters[4];

        $sequential = DB::transaction(function() use ($serieId, $customerId, $sellerId, $currencyId): int {

            $next = SaleHeader::getNewSequential($serieId);
            usleep(250000);

            DB::table("sales_header")->insert([
                "serie_id" => $serieId,
                "sequential" => $next,
                "holder_id" => $customerId,
                "seller_id" => $sellerId,
                "currency_id" => $currencyId,
                "issue_date" => now()->toDateString(),
                "total" => 1,
                "status" => "active",
            ]);

            return $next;

        });

        echo $sequential;
        exit(0);

    }

    if($action === "consume-stock") {

        InventoryMovementService::apply([
            "warehouse_id" => (int) $parameters[1],
            "item_id" => (int) $parameters[2],
            "movement_type" => InventoryMovementService::TYPE_EXIT,
            "origin_type" => InventoryMovementService::ORIGIN_MANUAL,
            "reason" => "Prueba de concurrencia",
            "quantity" => 1,
        ]);

        echo "consumed";
        exit(0);

    }

    throw new InvalidArgumentException("Acción de concurrencia desconocida.");

}catch(Throwable $exception) {

    fwrite(STDERR, $exception->getMessage());
    exit(1);

}
