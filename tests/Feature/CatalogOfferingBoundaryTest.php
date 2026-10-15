<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\System\Catalogs\Services\{ServiceService};
use App\Services\System\Catalogs\Subscriptions\{SubscriptionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class CatalogOfferingBoundaryTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_service_keeps_duration_and_capacity_without_inventory(): void {

        $service = ServiceService::create([
            "internal_code" => "SERVICE-VISUAL-TEST",
            "name" => "Servicio de prueba",
            "price" => 25,
            "currency_id" => $this->currencyId(),
            "estimated_duration_minutes" => 45,
            "capacity_control_enabled" => true,
            "capacity_limit" => 8,
            "commission_type" => "percentage",
            "commission_value" => 10,
            "see_my_web" => true,
            "see_my_web_price" => true,
            "status" => "active",
        ], $this->userId());

        $this->assertNotNull($service);
        $this->assertSame("service", $service->type);
        $this->assertSame(45, (int) $service->estimated_duration_minutes);
        $this->assertSame(8, (int) $service->capacity_limit);
        $this->assertTrue($service->hasCapacityControl());
        $this->assertSame(0, $service->warehouseItems()->count());

        $updated = ServiceService::update($service, ["capacity_control_enabled" => false], $this->userId());

        $this->assertFalse($updated->hasCapacityControl());
        $this->assertNull($updated->capacity_limit);

    }

    public function test_membership_keeps_attendance_and_benefits_without_inventory(): void {

        $membership = SubscriptionService::create([
            "internal_code" => "MEMBERSHIP-VISUAL-TEST",
            "name" => "Membresía de prueba",
            "price" => 100,
            "currency_id" => $this->currencyId(),
            "duration_type" => "month",
            "duration_value" => 1,
            "attendance_limit_per_day" => 2,
            "capacity_control_enabled" => true,
            "capacity_limit" => 20,
            "benefits" => ["Acceso a clases"],
            "restrictions" => ["Horario diurno"],
            "status" => "active",
        ], $this->userId());

        $this->assertNotNull($membership);
        $this->assertSame("subscription", $membership->type);
        $this->assertSame("month", $membership->duration_type);
        $this->assertSame(2, (int) $membership->attendance_limit_per_day);
        $this->assertSame(20, (int) $membership->capacity_limit);
        $this->assertSame(["Acceso a clases"], $membership->benefits);
        $this->assertSame(["Horario diurno"], $membership->restrictions);
        $this->assertSame(0, $membership->warehouseItems()->count());

    }

    private function currencyId(): int {

        return (int) DB::table("currencies")->where("code", "PEN")->value("id");

    }

    private function userId(): int {

        return (int) DB::table("users")->where("email", "admin@example.test")->value("id");

    }

}
