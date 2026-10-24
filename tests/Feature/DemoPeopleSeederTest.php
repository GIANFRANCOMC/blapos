<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\{AccessProfilesSeeder, DemoPeopleSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ProvisionsSystemDatabase;
use Tests\TestCase;

final class DemoPeopleSeederTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    public function test_demo_people_are_unique_inactive_where_appropriate_and_idempotent(): void {

        $this->provisionSystemDatabase();
        $seeder = app(DemoPeopleSeeder::class);
        $adminId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $profileGroups = (new \ReflectionClass(AccessProfilesSeeder::class))
            ->getReflectionConstant("PROFILE_PERMISSIONS")
            ->getValue();
        $routes = array_unique(array_merge(...array_map(
            fn(array $groups): array => array_merge(...array_column($groups, "routes")),
            array_values($profileGroups)
        )));
        $this->assertSame([], array_values(array_diff(
            $routes,
            DB::table("sub_sections")->pluck("dom_route")->all()
        )));

        $seeder->run(25, 25);

        $this->assertSame(25, DB::table("customers")
            ->where("email", "like", "cliente%")
            ->count());
        $this->assertSame(26, DB::table("customers")->count());
        $this->assertSame(25, DB::table("users")
            ->where("email", "like", "usuario%@example.test")
            ->count());
        $this->assertSame(25, DB::table("users")
            ->where("email", "like", "usuario%@example.test")
            ->where("status", "inactive")
            ->count());
        $this->assertDatabaseHas("customers", [
            "document_number" => "50000000",
            "email" => "cliente00001@example.test",
        ]);
        $profileIds = DB::table("roles")
            ->whereIn("slug", ["vendedor", "compras", "gestion", "auditoria", "contador"])
            ->pluck("id", "slug");
        $this->assertCount(5, $profileIds);
        $this->assertDatabaseMissing("roles", ["slug" => "demo-colaborador"]);
        $this->assertSame(5, DB::table("users")
            ->where("email", "like", "usuario%@example.test")
            ->distinct()
            ->count("role_id"));
        $this->assertSame(0, DB::table("roles")
            ->whereIn("id", $profileIds->values())
            ->where("is_full_access", true)
            ->count());
        foreach($profileIds as $roleId) {

            $this->assertGreaterThan(0, DB::table("role_sub_sections")->where("role_id", $roleId)->count());

        }
        $auditorRoleId = $profileIds["auditoria"];
        $auditorSalesActions = DB::table("role_sub_sections")
            ->where("role_id", $auditorRoleId)
            ->where("sub_section_id", DB::table("sub_sections")->where("dom_route", "sales.index")->value("id"))
            ->value("actions");
        $this->assertSame(["view", "export"], json_decode($auditorSalesActions, true));
        $this->assertSame(25, DB::table("users")
            ->where("email", "like", "usuario%@example.test")
            ->distinct()
            ->count("password"));

        $firstCollaboratorRoleId = DB::table("users")
            ->where("email", "usuario00001@example.test")
            ->value("role_id");

        $seeder->run(25, 25);

        $this->assertSame(25, DB::table("customers")
            ->where("email", "like", "cliente%")
            ->count());
        $this->assertSame(26, DB::table("customers")->count());
        $this->assertSame(26, DB::table("users")->count());
        $this->assertDatabaseHas("users", [
            "id" => $adminId,
            "email" => "admin@example.test",
            "status" => "active",
        ]);
        $this->assertSame(5, DB::table("roles")
            ->whereIn("slug", ["vendedor", "compras", "gestion", "auditoria", "contador"])
            ->count());
        $this->assertSame($firstCollaboratorRoleId, DB::table("users")
            ->where("email", "usuario00001@example.test")
            ->value("role_id"));

    }

    public function test_default_datasets_create_all_customers_and_inactive_collaborators(): void {

        $this->provisionSystemDatabase();

        app(DemoPeopleSeeder::class)->run();

        $this->assertSame(10001, DB::table("customers")->count());
        $this->assertSame(10001, DB::table("users")->count());
        $this->assertSame(10000, DB::table("users")
            ->where("status", "inactive")
            ->count());
        $this->assertSame(10000, DB::table("users")
            ->where("email", "like", "usuario%@example.test")
            ->distinct()
            ->count("password"));

    }

}
