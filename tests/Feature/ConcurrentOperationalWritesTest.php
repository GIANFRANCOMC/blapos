<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\{DatabaseMigrations};
use Illuminate\Support\Facades\{Cache, DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class ConcurrentOperationalWritesTest extends TestCase {
    use DatabaseMigrations;
    use ProvisionsSystemDatabase;

    public function test_independent_processes_respect_provisioning_series_and_stock_locks(): void {

        $this->provisionSystemDatabase();
        $this->assertProvisioningLock();

        $serieId = (int) DB::table("series")->value("id");
        $customerId = (int) DB::table("customers")->value("id");
        $sellerId = (int) DB::table("users")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");

        $saleResults = $this->runTogether("issue-sale", [
            $serieId, $customerId, $sellerId, $currencyId,
        ]);

        $numbers = array_column($saleResults, "output");
        sort($numbers);

        $this->assertSame([0, 0], array_column($saleResults, "exit_code"));
        $this->assertSame(["1", "2"], $numbers);
        $this->assertSame(2, DB::table("sales_header")->where("serie_id", $serieId)->count());

        $warehouseId = (int) DB::table("warehouses")->value("id");
        $itemId = (int) DB::table("items")->insertGetId([
            "internal_code" => "CONCURRENCY-PRODUCT",
            "name" => "Producto concurrente",
            "price" => 1,
            "currency_id" => $currencyId,
            "type" => "product",
            "status" => "active",
        ]);

        DB::table("warehouse_items")->insert([
            "warehouse_id" => $warehouseId,
            "item_id" => $itemId,
            "quantity" => 1,
            "minimum_stock" => 0,
            "average_cost" => 1,
            "inventory_value" => 1,
            "status" => "active",
        ]);

        $stockResults = $this->runTogether("consume-stock", [$warehouseId, $itemId]);
        $exitCodes = array_column($stockResults, "exit_code");
        sort($exitCodes);

        $this->assertSame([0, 1], $exitCodes);
        $this->assertSame(0.0, (float) DB::table("warehouse_items")
            ->where("warehouse_id", $warehouseId)
            ->where("item_id", $itemId)
            ->value("quantity"));

        $this->assertSame(1, DB::table("inventory_movements")
            ->where("warehouse_id", $warehouseId)
            ->where("item_id", $itemId)
            ->count());

    }

    private function assertProvisioningLock(): void {

        config(["cache.default" => "file"]);

        $slug = "concurrency-".bin2hex(random_bytes(6));
        $lock = Cache::lock("platform:tenant-provision:".hash("sha256", $slug), 30);

        $this->assertTrue($lock->get());

        try {

            $result = $this->runWorker("provision-lock", [$slug]);

            $this->assertSame(0, $result["exit_code"], $result["error"]);
            $this->assertSame("blocked", $result["output"]);

        }finally {

            $lock->release();

        }

    }

    private function runTogether(string $action, array $arguments): array {

        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR."blapos-concurrency-".bin2hex(random_bytes(8));
        $first = $this->startWorker($action, [$barrier, ...$arguments]);
        $second = $this->startWorker($action, [$barrier, ...$arguments]);

        try {

            usleep(500000);
            touch($barrier);

            return [$this->finishWorker($first), $this->finishWorker($second)];

        }finally {

            if(is_file($barrier)) {

                unlink($barrier);

            }

        }

    }

    private function runWorker(string $action, array $arguments): array {

        return $this->finishWorker($this->startWorker($action, $arguments));

    }

    private function startWorker(string $action, array $arguments): array {

        $command = [PHP_BINARY, base_path("tests/Support/ConcurrencyWorker.php"), $action, ...array_map("strval", $arguments)];
        $environment = array_merge(getenv(), [
            "APP_ENV" => "testing",
            "DB_CONNECTION" => "mysql",
            "DB_DATABASE" => config("database.connections.mysql.database"),
            "CACHE_DRIVER" => "file",
            "MAIL_MAILER" => "array",
        ]);

        $process = proc_open($command, [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ], $pipes, base_path(), $environment);

        $this->assertIsResource($process);
        fclose($pipes[0]);

        return [$process, $pipes];

    }

    private function finishWorker(array $worker): array {

        [$process, $pipes] = $worker;
        $output = trim(stream_get_contents($pipes[1]));
        $error = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            "exit_code" => proc_close($process),
            "output" => $output,
            "error" => $error,
        ];

    }
}
