<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Catalogs\Offerings\{OfferingImportTemplateExport, OfferingListExport};
use App\Imports\System\Catalogs\Offerings\{OfferingBasicImport};
use App\Models\System\Catalogs\{Item};
use App\Services\System\Catalogs\Services\{ServiceService};
use App\Services\System\Catalogs\Subscriptions\{SubscriptionService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\{Excel as ExcelFormat};
use Maatwebsite\Excel\Facades\{Excel};
use Maatwebsite\Excel\Imports\{HeadingRowFormatter};
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

    public function test_basic_membership_import_preserves_duration_without_inventory(): void {

        $import = new OfferingBasicImport(Item::TYPE_SUBSCRIPTION, $this->currencyId(), $this->userId());

        $import->collection(collect([
            collect([
                "nombre" => "Membresía cargada",
                "precio" => 80,
                "codigo_interno" => "MEM-IMPORT-01",
                "descripcion" => "Acceso mensual",
                "duracion_valor" => 1,
                "duracion_tipo" => "mes",
            ]),
        ]));

        $item = Item::query()->where("type", Item::TYPE_SUBSCRIPTION)->where("name", "Membresía cargada")->firstOrFail();

        $this->assertSame(1, $import->importedCount());
        $this->assertSame("month", $item->duration_type);
        $this->assertSame(0, $item->warehouseItems()->count());

    }

    public function test_basic_service_import_rolls_back_all_rows_when_one_is_invalid(): void {

        $import = new OfferingBasicImport(Item::TYPE_SERVICE, $this->currencyId(), $this->userId());

        try {

            $import->collection(collect([
                collect(["nombre" => "Servicio válido", "precio" => 30, "codigo_interno" => "SVC-IMPORT-01"]),
                collect(["nombre" => "Servicio inválido", "precio" => "", "codigo_interno" => "SVC-IMPORT-02"]),
            ]));

            $this->fail("La importación debía rechazar la segunda fila.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Fila 3", $exception->errors()["file"][0]);

        }

        $this->assertFalse(Item::query()->where("type", Item::TYPE_SERVICE)->where("name", "Servicio válido")->exists());

    }

    public function test_offering_spreadsheets_can_be_generated_for_both_types(): void {

        ServiceService::create([
            "internal_code" => "SVC-EXPORT-01",
            "name" => "Servicio exportable",
            "price" => 30,
            "currency_id" => $this->currencyId(),
            "status" => "active",
        ], $this->userId());

        SubscriptionService::create([
            "internal_code" => "MEM-EXPORT-01",
            "name" => "Membresía exportable",
            "price" => 80,
            "currency_id" => $this->currencyId(),
            "duration_value" => 1,
            "duration_type" => "month",
            "status" => "active",
        ], $this->userId());

        foreach([Item::TYPE_SERVICE, Item::TYPE_SUBSCRIPTION] as $type) {

            $templateExport = new OfferingImportTemplateExport($type);
            $template = Excel::raw($templateExport, ExcelFormat::XLSX);
            $listing = Excel::raw(new OfferingListExport($type), ExcelFormat::XLSX);
            $headings = HeadingRowFormatter::format($templateExport->headings());

            $this->assertStringStartsWith("PK", $template);
            $this->assertStringStartsWith("PK", $listing);
            $this->assertContains("codigo_interno", $headings);

            if($type === Item::TYPE_SERVICE) {

                $this->assertContains("duracion_estimada_minutos", $headings);

            }else {

                $this->assertContains("duracion_valor", $headings);
                $this->assertContains("duracion_tipo", $headings);

            }

        }

    }

    public function test_offering_import_and_export_routes_use_distinct_permissions(): void {

        foreach(["services", "subscriptions"] as $entity) {

            $this->assertSame("view", RolePermissionService::actionForRoute("{$entity}.index"));
            $this->assertSame("import", RolePermissionService::actionForRoute("{$entity}.import.template"));
            $this->assertSame("import", RolePermissionService::actionForRoute("{$entity}.import", "POST"));
            $this->assertSame("export", RolePermissionService::actionForRoute("{$entity}.export"));

        }

    }

    private function currencyId(): int {

        return (int) DB::table("currencies")->where("code", "PEN")->value("id");

    }

    private function userId(): int {

        return (int) DB::table("users")->where("email", "admin@example.test")->value("id");

    }

}
