<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exports\System\Organizations\{UserImportTemplateExport, UserListExport};
use App\Imports\System\Organizations\{UserImport};
use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB, Hash, Storage};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Concerns\{FromArray, WithHeadings};
use Maatwebsite\Excel\Facades\{Excel};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class UserTransferTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_import_is_atomic_and_creates_inactive_accounts_without_exposing_passwords(): void {

        $documentType = DB::table("identity_document_types")->where("code", "dni")->first(["code", "min_length"]);
        $role = DB::table("roles")->where("status", "active")->first(["name"]);
        $this->assertNotNull($documentType);
        $this->assertNotNull($role);

        $documentNumber = str_repeat("7", (int) $documentType->min_length);
        $row = [
            "perfil" => $role->name,
            "tipo_de_documento" => $documentType->code,
            "numero_de_documento" => $documentNumber,
            "nombre" => "Colaborador importado",
            "correo_electronico" => "importado@example.test",
        ];
        $actorId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");

        try {

            (new UserImport($actorId))->collection(collect([collect($row), collect($row)]));
            $this->fail("La carga duplicada debe rechazarse.");

        }catch(ValidationException $exception) {

            $this->assertStringContainsString("Fila 3", $exception->errors()["file"][0]);

        }

        $this->assertFalse(User::query()->where("email", "importado@example.test")->exists());

        $import = new UserImport($actorId);
        $import->collection(collect([collect($row)]));

        $user = User::query()->where("email", "importado@example.test")->firstOrFail();
        $this->assertSame(1, $import->importedCount());
        $this->assertSame("inactive", $user->status);
        $this->assertSame("inherit", $user->branch_scope_mode);
        $this->assertTrue(Hash::info($user->getRawOriginal("password"))["algoName"] !== "unknown");

        $export = new UserListExport(["filter_by" => "name", "word" => "Colaborador importado"]);
        $this->assertSame($documentNumber, $export->map($export->query()->firstOrFail())[2]);
        $this->assertNotContains("Contraseña", $export->headings());
        $this->assertSame("import", RolePermissionService::actionForRoute("users.import.template", "GET"));
        $this->assertSame("export", RolePermissionService::actionForRoute("users.export", "GET"));

    }

    public function test_excel_template_headings_are_read_by_the_real_importer(): void {

        $role = DB::table("roles")->where("status", "active")->first(["name"]);
        $this->assertNotNull($role);

        $sheet = new class($role->name) implements FromArray, WithHeadings {
            public function __construct(private readonly string $roleName) {
            }

            public function headings(): array {

                return (new UserImportTemplateExport())->headings();

            }

            public function array(): array {

                return [[$this->roleName, "dni", "87654321", "Colaborador Excel", "excel@example.test", null, null, null]];

            }
        };

        Storage::fake("local");
        Storage::disk("local")->put("colaboradores.xlsx", Excel::raw($sheet, \Maatwebsite\Excel\Excel::XLSX));

        $actorId = (int) DB::table("users")->where("email", "admin@example.test")->value("id");
        $import = new UserImport($actorId);

        Excel::import($import, Storage::disk("local")->path("colaboradores.xlsx"));

        $this->assertSame(1, $import->importedCount());
        $this->assertSame("inactive", User::query()->where("email", "excel@example.test")->value("status"));

    }
}
