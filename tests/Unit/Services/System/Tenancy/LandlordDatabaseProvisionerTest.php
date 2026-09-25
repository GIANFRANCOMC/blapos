<?php

declare(strict_types=1);

namespace Tests\Unit\Services\System\Tenancy;

use App\Services\System\Tenancy\{LandlordDatabaseProvisioner};
use Illuminate\Database\{Connection};
use Illuminate\Support\Facades\{DB};
use InvalidArgumentException;
use Mockery;
use PDO;
use RuntimeException;
use Tests\{TestCase};

final class LandlordDatabaseProvisionerTest extends TestCase {
    public function test_it_does_not_create_an_existing_landlord_database(): void {

        config(["tenancy.landlord_connection" => "landlord"]);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive("getConfig")
            ->once()
            ->andReturn($this->connectionConfig());

        $connection->shouldReceive("getPdo")
            ->once()
            ->andReturn(Mockery::mock(PDO::class));

        DB::shouldReceive("connection")
            ->once()
            ->with("landlord")
            ->andReturn($connection);
        DB::shouldReceive("purge")->never();

        $this->assertFalse((new LandlordDatabaseProvisioner())->ensureExists());

    }

    public function test_it_creates_a_missing_landlord_database(): void {

        config(["tenancy.landlord_connection" => "landlord"]);

        $landlordConnection = Mockery::mock(Connection::class);
        $landlordConnection->shouldReceive("getConfig")
            ->once()
            ->andReturn($this->connectionConfig());

        $landlordConnection->shouldReceive("getPdo")
            ->once()
            ->andThrow(new RuntimeException("Unknown database"));

        $provisioningConnection = Mockery::mock(Connection::class);
        $provisioningConnection->shouldReceive("statement")
            ->once()
            ->with("CREATE DATABASE IF NOT EXISTS `blapos_landlord` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
            ->andReturnTrue();

        DB::shouldReceive("connection")
            ->once()
            ->with("landlord")
            ->andReturn($landlordConnection);
        DB::shouldReceive("purge")
            ->once()
            ->with("landlord");
        DB::shouldReceive("connection")
            ->once()
            ->with("landlord_provisioning")
            ->andReturn($provisioningConnection);
        DB::shouldReceive("purge")
            ->once()
            ->with("landlord_provisioning");

        $this->assertTrue((new LandlordDatabaseProvisioner())->ensureExists());

    }

    public function test_it_rejects_an_unsafe_database_name(): void {

        config(["tenancy.landlord_connection" => "landlord"]);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive("getConfig")
            ->once()
            ->andReturn($this->connectionConfig("blapos_landlord; DROP DATABASE mysql"));

        $connection->shouldReceive("getPdo")->never();

        DB::shouldReceive("connection")
            ->once()
            ->with("landlord")
            ->andReturn($connection);

        $this->expectException(InvalidArgumentException::class);

        (new LandlordDatabaseProvisioner())->ensureExists();

    }

    private function connectionConfig(string $database = "blapos_landlord"): array {

        return [
            "driver" => "mysql",
            "host" => "127.0.0.1",
            "port" => "3306",
            "database" => $database,
            "username" => "root",
            "password" => "",
            "unix_socket" => "",
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
            "prefix" => "",
            "options" => [],
            "url" => null,
        ];

    }
}
