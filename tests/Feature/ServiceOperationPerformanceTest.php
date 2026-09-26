<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\System\Operations\{ServiceOperationConfigService, ServiceOperationService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class ServiceOperationPerformanceTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    private int $branchId;

    private int $userId;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

        $this->branchId = (int) DB::table("branches")
            ->value("id");

        $this->userId = (int) DB::table("users")
            ->where("email", "admin@example.test")
            ->value("id");

    }

    public function test_initial_configuration_does_not_embed_growing_catalogs(): void {

        $this->seedOperationOptions(40);
        ServiceOperationConfigService::clearCache();

        $params = ServiceOperationConfigService::getInitParams("restaurant", $this->userId);

        $this->assertSame([], $params->config->customers);
        $this->assertSame([], $params->config->items);
        $this->assertLessThan(20000, strlen(json_encode($params, JSON_THROW_ON_ERROR)));

    }

    public function test_remote_options_are_tenant_scoped_minimal_and_limited(): void {

        $this->seedOperationOptions(40);

        $customers = ServiceOperationService::options("customers", "Cliente");
        $items = ServiceOperationService::options("items", "Servicio", "service");

        $this->assertCount(30, $customers);
        $this->assertCount(30, $items);
        $this->assertSame(["id", "name", "document_number"], array_keys((array) $customers->first()));
        $this->assertSame(["id", "name", "type", "price"], array_keys((array) $items->first()));

    }

    public function test_restaurant_board_returns_floors_and_stations_in_one_composition(): void {

        $floorId = DB::table("service_floors")->insertGetId([
            "branch_id" => $this->branchId,
            "code" => "MAIN",
            "name" => "Salón principal",
            "level_number" => 1,
            "sort_order" => 1,
            "status" => "active",
        ]);
        DB::table("service_stations")->insert([
            "branch_id" => $this->branchId,
            "service_floor_id" => $floorId,
            "code" => "M01",
            "name" => "Mesa 1",
            "station_type" => "table",
            "capacity" => 4,
            "position_x" => 0,
            "position_y" => 0,
            "color" => "#2563EB",
            "shape" => "round",
            "status" => "active",
        ]);

        $board = ServiceOperationService::board($this->userId, $this->branchId);

        $this->assertSame($floorId, $board["selected_floor_id"]);
        $this->assertCount(1, $board["floors"]);
        $this->assertCount(1, $board["stations"]);
        $this->assertSame("Mesa 1", $board["stations"]->first()->name);

    }

    private function seedOperationOptions(int $count): void {

        $identityDocumentTypeId = (int) DB::table("identity_document_types")
            ->value("id");

        $currencyId = (int) DB::table("currencies")
            ->value("id");

        $now = now();
        $customers = [];
        $items = [];

        foreach(range(1, $count) as $number) {

            $customers[] = [
                "identity_document_type_id" => $identityDocumentTypeId,
                "document_number" => str_pad((string) $number, 8, "0", STR_PAD_LEFT),
                "name" => "Cliente {$number}",
                "status" => "active",
                "created_at" => $now,
            ];

            $items[] = [
                "internal_code" => "SERVICE-{$number}",
                "name" => "Servicio {$number}",
                "price" => 10,
                "currency_id" => $currencyId,
                "type" => "service",
                "capacity_control_enabled" => false,
                "capacity_used" => 0,
                "status" => "active",
                "created_at" => $now,
            ];

        }

        DB::table("customers")->insert($customers);
        DB::table("items")->insert($items);

    }
}
