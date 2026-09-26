<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\System\Customers\Tracking\{TrackingAttendanceBusinessService};
use App\Services\System\Organizations\Users\{UserAttendanceService};
use Carbon\{Carbon};
use DomainException;
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class AttendanceFlowsTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    private int $branchId;

    private int $currencyId;

    private int $customerId;

    private int $seriesId;

    private int $userId;

    private int $warehouseId;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

        $identityId = DB::table("identity_document_types")
            ->where("code", "dni")
            ->value("id");
        DB::table("customers")->updateOrInsert(
            ["document_number" => "70000002"],
            [
                "identity_document_type_id" => $identityId,
                "document_number" => "70000002",
                "name" => "Cliente de pruebas",
                "status" => "active",
                "updated_at" => now(),
            ]
        );

        $this->branchId = (int) DB::table("branches")->where("name", "Sede Principal")->value("id");
        $this->currencyId = (int) DB::table("currencies")->where("code", "PEN")->value("id");
        $this->customerId = (int) DB::table("customers")->where("document_number", "70000002")->value("id");
        $this->seriesId = (int) DB::table("series")->where("branch_id", $this->branchId)->value("id");
        $this->userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $this->warehouseId = (int) DB::table("warehouses")->where("branch_id", $this->branchId)->value("id");

    }

    public function test_customer_can_check_in_and_check_out(): void {

        $this->createCustomerSubscription($this->customerId, 1);
        $service = app(TrackingAttendanceBusinessService::class);
        $checkInAt = Carbon::parse("2026-07-02 08:00:00");

        $checkIn = $service->validateAndCreateAttendance([
            "branch_id" => $this->branchId,
            "customer_id" => $this->customerId,
            "start_date" => $checkInAt,
            "end_date" => null,
            "user_id" => $this->userId,
            "type" => "manual_form",
            "action" => "checkin",
        ]);

        $this->assertTrue($checkIn["bool"]);
        $this->assertDatabaseHas("attendances", [
            "branch_id" => $this->branchId,
            "customer_id" => $this->customerId,
            "status" => "active",
        ]);

        $checkOut = $service->validateAndCreateAttendance([
            "branch_id" => $this->branchId,
            "customer_id" => $this->customerId,
            "start_date" => null,
            "end_date" => $checkInAt->copy()->addHours(2),
            "user_id" => $this->userId,
            "action" => "checkout",
        ]);

        $this->assertTrue($checkOut["bool"]);
        $this->assertDatabaseHas("attendances", [
            "branch_id" => $this->branchId,
            "customer_id" => $this->customerId,
            "status" => "finalized",
        ]);

    }

    public function test_customer_daily_limit_blocks_an_extra_check_in(): void {

        $this->createCustomerSubscription($this->customerId, 1);
        $date = Carbon::parse("2026-07-02 10:00:00");

        DB::table("attendances")->insert([
            "branch_id" => $this->branchId,
            "customer_id" => $this->customerId,
            "start_date" => $date->copy()->subHours(2),
            "end_date" => $date->copy()->subHour(),
            "type" => "manual_form",
            "status" => "finalized",
            "created_at" => now(),
            "created_by" => $this->userId,
        ]);

        $result = app(TrackingAttendanceBusinessService::class)->validateAndCreateAttendance([
            "branch_id" => $this->branchId,
            "customer_id" => $this->customerId,
            "start_date" => $date,
            "end_date" => null,
            "user_id" => $this->userId,
            "type" => "manual_form",
            "action" => "checkin",
        ]);

        $this->assertFalse($result["bool"]);
        $this->assertStringContainsString("límite diario", $result["msg"]);
        $this->assertDatabaseCount("attendances", 1);

    }

    public function test_collaborator_check_in_check_out_and_weekly_hours(): void {

        $checkInAt = Carbon::parse("2026-07-02 08:00:00");

        $attendance = UserAttendanceService::checkIn([
            "branch_id" => $this->branchId,
            "user_id" => $this->userId,
            "actor_id" => $this->userId,
            "checked_in_at" => $checkInAt,
        ]);

        $this->assertSame(UserAttendanceService::STATUS_ACTIVE, $attendance->status);

        $attendance = UserAttendanceService::checkOut([
            "branch_id" => $this->branchId,
            "user_id" => $this->userId,
            "actor_id" => $this->userId,
            "checked_out_at" => $checkInAt->copy()->addHours(8),
        ]);

        $this->assertSame(UserAttendanceService::STATUS_FINALIZED, $attendance->status);
        $this->assertSame(480, $attendance->worked_minutes);

        $summary = UserAttendanceService::weeklySummary($this->userId, "2026-06-29");

        $this->assertSame(480, $summary["total_minutes"]);
        $this->assertSame(8.0, $summary["total_hours"]);
        $this->assertCount(1, $summary["days"]);

    }

    public function test_collaborator_cannot_open_simultaneous_shifts_in_two_branches(): void {

        DB::table("branches")->insert([
            "company_id" => 1,
            "internal_code" => "SUC-TEST-2",
            "name" => "Sede secundaria",
            "status" => "active",
            "created_at" => now(),
        ]);

        $secondBranchId = (int) DB::table("branches")->where("internal_code", "SUC-TEST-2")->value("id");

        UserAttendanceService::checkIn([
            "branch_id" => $this->branchId,
            "user_id" => $this->userId,
            "actor_id" => $this->userId,
            "checked_in_at" => "2026-07-02 08:00:00",
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("jornada en curso");

        UserAttendanceService::checkIn([
            "branch_id" => $secondBranchId,
            "user_id" => $this->userId,
            "actor_id" => $this->userId,
            "checked_in_at" => "2026-07-02 09:00:00",
        ]);

    }

    private function createCustomerSubscription(int $customerId, int $limit): void {

        $itemId = (int) DB::table("items")->insertGetId([
            "internal_code" => "MEM-ATT-TEST",
            "name" => "Membresía de prueba",
            "price" => 10,
            "currency_id" => $this->currencyId,
            "type" => "subscription",
            "status" => "active",
            "created_at" => now(),
        ]);

        $saleId = (int) DB::table("sales_header")->insertGetId([
            "serie_id" => $this->seriesId,
            "sequential" => 900001,
            "holder_id" => $customerId,
            "seller_id" => $this->userId,
            "currency_id" => $this->currencyId,
            "warehouse_id" => $this->warehouseId,
            "issue_date" => "2026-07-01",
            "subtotal" => 10,
            "tax" => 0,
            "total" => 10,
            "status" => "active",
            "created_at" => now(),
        ]);

        $saleBodyId = (int) DB::table("sales_body")->insertGetId([
            "sale_header_id" => $saleId,
            "item_id" => $itemId,
            "currency_id" => $this->currencyId,
            "name" => "Membresía de prueba",
            "quantity" => 1,
            "price" => 10,
            "price_includes_tax" => true,
            "total" => 10,
            "customer_id" => $customerId,
            "type" => "subscription",
            "extras" => "{}",
            "status" => "active",
            "created_at" => now(),
        ]);

        DB::table("subscriptions")->insert([
            "branch_id" => $this->branchId,
            "sale_header_id" => $saleId,
            "sale_body_id" => $saleBodyId,
            "customer_id" => $customerId,
            "duration_type" => "month",
            "duration_value" => 1,
            "start_date" => "2026-07-01 00:00:00",
            "end_date" => "2026-07-31 23:59:59",
            "attendance_limit_per_day" => $limit,
            "type" => "sale",
            "status" => "active",
            "created_at" => now(),
        ]);

    }
}
