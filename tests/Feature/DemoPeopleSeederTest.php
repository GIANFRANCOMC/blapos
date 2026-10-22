<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\DemoPeopleSeeder;
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
        $this->assertDatabaseHas("roles", [
            "slug" => "demo-colaborador",
            "is_full_access" => false,
        ]);
        $this->assertSame(25, DB::table("users")
            ->where("email", "like", "usuario%@example.test")
            ->distinct()
            ->count("password"));

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
