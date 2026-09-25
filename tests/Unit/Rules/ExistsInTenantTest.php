<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\System\Defaults\{ExistsInTenant};
use Illuminate\Support\Facades\{DB};
use Mockery;
use Tests\{TestCase};

class ExistsInTenantTest extends TestCase {
    public function test_it_accepts_an_available_tenant_record(): void {

        $query = $this->mockQuery(true);

        DB::shouldReceive("table")->once()->with("brands")->andReturn($query);
        $query->shouldReceive("where")->once()->with("id", 1)->andReturnSelf();
        $query->shouldReceive("where")->once()->with("status", "active")->andReturnSelf();

        $rule = new ExistsInTenant("brands", ["status" => "active"]);

        $this->assertSame([], $this->validateRule($rule, 1));

    }

    public function test_it_supports_joins_and_a_qualified_key(): void {

        $query = $this->mockQuery(true);

        DB::shouldReceive("table")->once()->with("warehouses")->andReturn($query);
        $query->shouldReceive("join")->once()->with("branches", "warehouses.branch_id", "=", "branches.id")->andReturnSelf();
        $query->shouldReceive("where")->once()->with("warehouses.id", 20)->andReturnSelf();
        $query->shouldReceive("where")->once()->with("warehouses.status", "active")->andReturnSelf();
        $query->shouldReceive("where")->once()->with("branches.status", "active")->andReturnSelf();

        $rule = new ExistsInTenant(
            "warehouses",
            ["warehouses.status" => "active", "branches.status" => "active"],
            null,
            [["branches", "warehouses.branch_id", "=", "branches.id"]],
            "warehouses.id"
        );

        $this->assertSame([], $this->validateRule($rule, 20));

    }

    private function mockQuery(bool $exists) {

        $query = Mockery::mock();
        $query->shouldReceive("exists")->once()->andReturn($exists);

        return $query;

    }

    private function validateRule(ExistsInTenant $rule, int $value): array {

        $errors = [];

        $rule->validate("record_id", $value, function(string $message) use (&$errors) {

            $errors[] = $message;

        });

        return $errors;

    }
}
