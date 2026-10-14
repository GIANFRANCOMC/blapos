<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Customers\{CustomerImportTemplateExport, CustomerListExport};
use App\Imports\System\Customers\{CustomerImport};
use App\Models\System\Customers\{Customer};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\Facades\{Storage};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Concerns\{FromArray, WithHeadings};
use Maatwebsite\Excel\Facades\{Excel};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class CustomerTransferTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_import_is_atomic_and_export_reuses_customer_filters(): void {

        $documentType = DB::table("identity_document_types")
            ->where("status", "active")
            ->first(["id", "code", "min_length"]);

        $this->assertNotNull($documentType);

        $documentNumber = str_repeat("7", max(1, (int) $documentType->min_length));
        $row = [
            "tipo_de_documento" => $documentType->code,
            "numero_de_documento" => $documentNumber,
            "nombre" => "Cliente de prueba Excel",
            "correo_electronico" => "cliente@example.test",
            "estado" => "active",
        ];
        $userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");

        try {

            (new CustomerImport($userId))->collection(collect([collect($row), collect($row)]));
            $this->fail("La carga duplicada debe rechazarse.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Fila 3", $exception->errors()["file"][0]);

        }

        $this->assertFalse(Customer::query()->where("document_number", $documentNumber)->exists());

        $import = new CustomerImport($userId);
        $import->collection(collect([collect($row)]));

        $this->assertSame(1, $import->importedCount());

        $export = new CustomerListExport(["filter_by" => "name", "word" => "Cliente de prueba Excel"]);
        $customer = $export->query()->firstOrFail();

        $this->assertSame($documentNumber, $export->map($customer)[1]);
        $this->assertCount(8, $export->headings());
        $this->assertCount(11, (new CustomerImportTemplateExport())->headings());
        $this->assertSame("import", RolePermissionService::actionForRoute("customers.import.template", "GET"));
        $this->assertSame("export", RolePermissionService::actionForRoute("customers.export", "GET"));

    }

    public function test_excel_template_headings_are_read_by_the_real_importer(): void {

        $documentType = DB::table("identity_document_types")
            ->where("status", "active")
            ->first(["code", "min_length"]);
        $this->assertNotNull($documentType);

        $documentNumber = str_repeat("8", max(1, (int) $documentType->min_length));
        $sheet = new class($documentType->code, $documentNumber) implements FromArray, WithHeadings {
            public function __construct(private readonly string $typeCode, private readonly string $documentNumber) {
            }

            public function headings(): array {

                return (new CustomerImportTemplateExport())->headings();

            }

            public function array(): array {

                return [[$this->typeCode, $this->documentNumber, "Cliente desde XLSX", null, null, null, null, null, null, null, "active"]];

            }
        };

        Storage::fake("local");
        Storage::disk("local")->put("clientes.xlsx", Excel::raw($sheet, \Maatwebsite\Excel\Excel::XLSX));

        $userId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $import = new CustomerImport($userId);

        Excel::import($import, Storage::disk("local")->path("clientes.xlsx"));

        $this->assertSame(1, $import->importedCount());
        $this->assertSame("Cliente desde XLSX", Customer::query()->where("document_number", $documentNumber)->value("name"));

    }
}
