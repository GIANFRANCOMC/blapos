<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\System\Tenancy\{PlatformSupportService};
use Illuminate\Support\Facades\{DB};
use Mockery;
use RuntimeException;
use Tests\{TestCase};

final class PlatformSupportServiceTest extends TestCase {

    public function test_contact_reads_configured_values_from_landlord(): void {

        $schema = Mockery::mock();
        $schema->shouldReceive("hasTable")->once()->with("platform_settings")->andReturn(true);

        $query = Mockery::mock();
        $query->shouldReceive("whereIn")->once()->with("key", ["support_email", "support_phone"])->andReturnSelf();
        $query->shouldReceive("pluck")->once()->with("value", "key")->andReturn(collect([
            "support_email" => "ayuda@example.test",
            "support_phone" => "+51 900 123 456",
        ]));

        $database = Mockery::mock();
        $database->shouldReceive("getSchemaBuilder")->once()->andReturn($schema);
        $database->shouldReceive("table")->once()->with("platform_settings")->andReturn($query);
        DB::shouldReceive("connection")->once()->with("landlord")->andReturn($database);

        $this->assertSame([
            "email" => "ayuda@example.test",
            "phone" => "+51 900 123 456",
            "phone_href" => "+51900123456",
        ], app(PlatformSupportService::class)->contact());

    }

    public function test_contact_remains_available_when_landlord_is_unavailable(): void {

        DB::shouldReceive("connection")->once()->andThrow(new RuntimeException("Database unavailable"));

        $this->assertSame([
            "email" => PlatformSupportService::DEFAULT_EMAIL,
            "phone" => PlatformSupportService::DEFAULT_PHONE,
            "phone_href" => "+51987057624",
        ], app(PlatformSupportService::class)->contact());

    }

}
