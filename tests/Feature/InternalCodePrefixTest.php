<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureModulePermission, EnsureOperationalScope, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Organizations\{User};
use App\Services\System\General\{MasterDataService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class InternalCodePrefixTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    public function test_editors_validate_and_persist_the_complete_internal_code(): void {

        $this->provisionSystemDatabase();
        $user = User::query()->where("email", "admin@example.test")->firstOrFail();
        $this->actingAs($user)->withoutMiddleware([
            ResolveTenant::class, TrustHosts::class, EnsureTenantSession::class,
            EnsureAuthenticatedSession::class, EnsureModulePermission::class, EnsureOperationalScope::class,
        ]);

        $common = ["internal_code" => "ABC123", "name" => "Prueba de prefijo", "status" => "active"];
        $commercial = [
            "price" => 10,
            "currency_id" => DB::table("currencies")->where("code", "PEN")->value("id"),
            "see_my_web" => true,
            "see_my_web_price" => false,
        ];
        $entities = [
            ["products", "items", "PROD", $commercial + [
                "barcode" => "5901234123457",
                "inventory" => [["warehouse_id" => DB::table("warehouses")->value("id"), "initial_stock" => 0, "minimum_stock" => 0]],
            ]],
            ["services", "items", "SERV", $commercial],
            ["subscriptions", "items", "MEMB", $commercial + ["duration_type" => "day", "duration_value" => 7]],
            ["brands", "brands", "MAR", []],
            ["categories", "categories", "CAT", []],
            ["branches", "branches", "SUC", []],
            ["assets", "assets", "ACT", []],
        ];

        foreach($entities as [$route, $table, $prefix, $fields]) {
            $payload = $common + $fields;
            $this->postJson(route("{$route}.store"), array_replace($payload, ["internal_code" => "AB C123"]))
                ->assertUnprocessable()->assertJsonValidationErrors("internal_code");
            $this->postJson(route("{$route}.store"), $payload)->assertSuccessful()->assertJsonPath("bool", true);
            $id = DB::table($table)->where("internal_code", "{$prefix}-ABC123")->value("id");
            $this->assertNotNull($id, $route);

            $payload["internal_code"] = "XYZ789";
            $this->patchJson(route("{$route}.update", ["id" => $id]), $payload)->assertOk();
            $this->assertDatabaseHas($table, ["id" => $id, "internal_code" => "{$prefix}-XYZ789"]);
        }

        $product = $common + $entities[0][3];
        $product["barcode"] = "5901 234123457";
        $this->postJson(route("products.store"), $product)->assertUnprocessable()->assertJsonValidationErrors("barcode");

        $membership = $common + $commercial + ["duration_type" => "day", "duration_value" => 7];
        foreach(["memb-XYZ789", "MEMB-", " MEMB-TEST", "MEMB- TEST", "TEST\n", "TEST\u{00A0}ABC", ["code" => "XYZ789"], str_repeat("A", 46)] as $invalidCode) {
            $membership["internal_code"] = $invalidCode;
            $this->postJson(route("subscriptions.store"), $membership)->assertUnprocessable()->assertJsonValidationErrors("internal_code");
        }

        $id = DB::table("items")->where("type", "subscription")->where("internal_code", "MEMB-XYZ789")->value("id");
        $settingId = DB::table("company_settings")->where("group", "internal_code_prefixes")->where("key", "subscription")->value("id");
        $settings = ["group" => "internal_code_prefixes", "key" => "subscription", "value_type" => "string", "status" => "active"];
        MasterDataService::save($user->id, "company-settings", $settings + ["value" => " - gym - "], $settingId);
        $this->assertDatabaseHas("items", ["id" => $id, "internal_code" => "MEMB-XYZ789"]);

        $membership["internal_code"] = "MEMB-XYZ789";
        $this->patchJson(route("subscriptions.update", ["id" => $id]), $membership)->assertOk();
        $this->assertDatabaseHas("items", ["id" => $id, "internal_code" => "MEMB-XYZ789"]);
        $membership["internal_code"] = "PLAN-NUEVO";
        $this->postJson(route("subscriptions.store"), $membership)->assertSuccessful()->assertJsonPath("bool", true);
        $this->getJson(route("subscriptions.list", ["filter_by" => "internal_code", "word" => "GYM-PLAN-NUEVO"]))
            ->assertOk()->assertJsonFragment(["internal_code" => "GYM-PLAN-NUEVO"]);

        $membership["internal_code"] = "CODIGO-EDITADO";
        $this->patchJson(route("subscriptions.update", ["id" => $id]), $membership)->assertOk();
        $this->assertDatabaseHas("items", ["id" => $id, "internal_code" => "GYM-CODIGO-EDITADO"]);

        MasterDataService::save($user->id, "company-settings", $settings + ["value" => null], $settingId);
        $membership["internal_code"] = "GYM-CODIGO-EDITADO";
        $this->patchJson(route("subscriptions.update", ["id" => $id]), $membership)->assertOk();
        $this->assertDatabaseHas("items", ["id" => $id, "internal_code" => "GYM-CODIGO-EDITADO"]);
        $membership["internal_code"] = "SIN-PREFIJO";
        $this->postJson(route("subscriptions.store"), $membership)->assertSuccessful()->assertJsonPath("bool", true);
        $this->assertDatabaseHas("items", ["type" => "subscription", "internal_code" => "SIN-PREFIJO"]);

    }

}
