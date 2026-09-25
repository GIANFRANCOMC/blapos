<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\System\Defaults\{UniqueInTenant};
use Illuminate\Support\Facades\{DB};
use Mockery;
use Tests\{TestCase};

class UniqueInTenantTest extends TestCase {
    public function test_it_rejects_a_value_already_used_in_the_tenant(): void {

        $query = $this->mockQuery(true);

        DB::shouldReceive("table")->once()->with("items")->andReturn($query);
        $query->shouldReceive("where")->once()->with("barcode", "2001234567893")->andReturnSelf();

        $rule = new UniqueInTenant("items", "barcode", null, [], "código de barras");

        $this->assertNotSame([], $this->validateRule($rule, "barcode", "2001234567893"));

    }

    public function test_it_supports_scopes_and_excludes_the_current_record(): void {

        $query = $this->mockQuery(false);

        DB::shouldReceive("table")->once()->with("items")->andReturn($query);
        $query->shouldReceive("where")->once()->with("internal_code", "PROD-001")->andReturnSelf();
        $query->shouldReceive("where")->once()->with("type", "product")->andReturnSelf();
        $query->shouldReceive("where")->once()->with("id", "!=", 25)->andReturnSelf();

        $rule = new UniqueInTenant("items", "internal_code", 25, ["type" => "product"], "código interno");

        $this->assertSame([], $this->validateRule($rule, "internal_code", "PROD-001"));

    }

    private function mockQuery(bool $exists) {

        $query = Mockery::mock();
        $query->shouldReceive("exists")->once()->andReturn($exists);

        return $query;

    }

    private function validateRule(UniqueInTenant $rule, string $attribute, string $value): array {

        $errors = [];

        $rule->validate($attribute, $value, function(string $message) use (&$errors) {

            $errors[] = $message;

        });

        return $errors;

    }
}
