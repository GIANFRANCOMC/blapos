<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\System\{Numbers};
use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Organizations\Branches\{SerieService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class CommercialPerformanceTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    private const ROUTE = "analytics.commercial_performance.";
    private const FROM = "2026-11-01";
    private const TO = "2026-11-03";

    private User $admin;
    private int $branchId;
    private int $otherBranchId;
    private int $customerId;
    private int $pen;
    private int $usd;
    private int $sequential = 99000;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();
        $this->withoutVite();
        $this->withoutMiddleware([ResolveTenant::class, TrustHosts::class, EnsureTenantSession::class, EnsureAuthenticatedSession::class]);
        $this->admin = User::query()->where("email", "admin@example.test")->firstOrFail();
        $this->branchId = (int) DB::table("branches")->where("name", "Sede Principal")->value("id");
        $this->otherBranchId = DB::table("branches")->insertGetId([
            "company_id" => 1,
            "internal_code" => "SUC-REND",
            "name" => "Sucursal secundaria",
            "status" => "inactive",
        ]);
        SerieService::createForBranch($this->otherBranchId);
        $this->customerId = DB::table("customers")->insertGetId([
            "identity_document_type_id" => DB::table("identity_document_types")->where("code", "dni")->value("id"),
            "document_number" => "70000292",
            "name" => "Cliente de prueba",
            "status" => "active",
        ]);
        $this->pen = (int) DB::table("currencies")->where("code", "PEN")->value("id");
        $this->usd = DB::table("currencies")->insertGetId([
            "code" => "USD",
            "sign" => "$",
            "singular_name" => "Dólar",
            "plural_name" => "Dólares",
        ]);

    }

    public function test_reports_active_historical_lines_by_item_type_currency_and_issue_date(): void {

        $product = $this->item("P", "product");
        $service = $this->item("S", "service");
        $plan = $this->item("M", "subscription");
        $this->sale($product, "product", "Nombre anterior", self::FROM, $this->branchId, $this->pen, "12.345");
        $this->sale($product, "product", "Nombre histórico", self::TO, $this->branchId, $this->pen, "20.435");
        $this->sale($product, "product", "Producto en dólares", self::TO, $this->branchId, $this->usd, "5");
        $this->sale($service, "service", "Corte", self::FROM, $this->branchId, $this->pen, "10");
        $this->sale($plan, "subscription", "Plan mensual", self::TO, $this->otherBranchId, $this->pen, "120");
        $this->sale($product, "product", "Fuera del periodo", "2026-10-31", $this->branchId, $this->pen, "90");
        $this->sale($product, "product", "Línea anulada", self::FROM, $this->branchId, $this->pen, "50", lineStatus: "canceled");
        $this->sale($product, "product", "Venta anulada", self::TO, $this->branchId, $this->pen, "80", saleStatus: "canceled");

        $this->actingAs($this->admin)->get(route(self::ROUTE."index"))->assertOk();
        $response = $this->getJson($this->listUrl())->assertOk()
            ->assertJsonPath("data.items.total", 4)
            ->assertJsonPath("data.items.per_page", 10)
            ->assertJsonPath("data.evolution.days", [self::FROM, "2026-11-02", self::TO])
            ->assertJsonPath("data.evolution.series.0.code", "PEN")
            ->assertJsonPath("data.evolution.series.0.totals", array_map(Numbers::storage(...), ["22.345", "0", "140.435"]))
            ->assertJsonPath("data.evolution.series.1.code", "USD")
            ->assertJsonPath("data.evolution.series.1.totals", array_map(Numbers::storage(...), ["0", "0", "5"]));

        $this->assertSame([
            "item_id" => $product,
            "name" => "Nombre histórico",
            "type" => "product",
            "currency_id" => $this->pen,
            "code" => "PEN",
            "sign" => "S/",
            "quantity" => Numbers::storage("2"),
            "total" => Numbers::storage("32.780"),
        ], collect($response->json("data.items.data"))->firstWhere("name", "Nombre histórico"));

        $this->getJson($this->listUrl(["type" => "subscription", "branch_id" => $this->otherBranchId]))
            ->assertOk()->assertJsonPath("data.items.total", 1)
            ->assertJsonPath("data.items.data.0.name", "Plan mensual")
            ->assertJsonPath("data.evolution.series.0.totals.2", Numbers::storage("120"));

        $this->getJson($this->listUrl(["type" => "subscription", "branch_id" => $this->branchId]))
            ->assertOk()->assertJsonPath("data.items.total", 0)->assertJsonPath("data.evolution.series", []);

    }

    public function test_pagination_keeps_the_report_scope_and_can_omit_evolution(): void {

        for($i = 1; $i <= 11; $i++) {
            $name = "Servicio ".str_pad((string) $i, 2, "0", STR_PAD_LEFT);
            $this->sale($this->item($name, "service"), "service", $name, self::FROM, $this->branchId, $this->pen, "8");
        }

        $this->actingAs($this->admin)->getJson($this->listUrl(["type" => "service", "page" => 2, "include_evolution" => 0]))
            ->assertOk()->assertJsonPath("data.items.total", 11)->assertJsonPath("data.items.current_page", 2)
            ->assertJsonCount(1, "data.items.data")->assertJsonPath("data.items.data.0.name", "Servicio 11")
            ->assertJsonPath("data.evolution", null);

    }

    public function test_role_needs_its_own_permission_and_sees_only_authorized_branches(): void {

        $product = $this->item("P", "product");
        $this->sale($product, "product", "Producto", self::FROM, $this->branchId, $this->pen, "10");
        $this->sale($product, "product", "Sucursal fuera de alcance", self::FROM, $this->otherBranchId, $this->pen, "90");
        $roleId = DB::table("roles")->insertGetId([
            "slug" => "rendimiento-reader",
            "name" => "Perfil de prueba",
            "is_full_access" => false,
            "branch_scope_mode" => "restricted",
            "status" => "active",
        ]);
        DB::table("role_branches")->insert(["role_id" => $roleId, "branch_id" => $this->branchId]);
        DB::table("role_sub_sections")->insert(["role_id" => $roleId, "sub_section_id" => 20, "actions" => '["view"]', "status" => "active"]);
        $this->admin->update(["role_id" => $roleId]);
        $this->actingAs($this->admin->fresh());

        $this->getJson(route(self::ROUTE."index"))->assertForbidden();
        $this->getJson(route(self::ROUTE."initParams"))->assertForbidden();
        $this->getJson($this->listUrl())->assertForbidden();

        DB::table("role_sub_sections")->insert(["role_id" => $roleId, "sub_section_id" => 110, "actions" => '["view"]', "status" => "active"]);
        CompanySectionService::clearTenantCache();

        $this->getJson(route(self::ROUTE."initParams"))->assertOk()
            ->assertJsonCount(1, "data.branches")->assertJsonPath("data.branches.0.id", $this->branchId);
        $this->getJson($this->listUrl())->assertOk()->assertJsonPath("data.items.total", 1)
            ->assertJsonPath("data.items.data.0.name", "Producto")
            ->assertJsonPath("data.evolution.series.0.totals.0", Numbers::storage("10"));
        $this->getJson($this->listUrl(["branch_id" => $this->otherBranchId]))->assertForbidden();

        DB::table("role_branches")->where("role_id", $roleId)->delete();
        AccessScopeService::clearUserCache($this->admin->id);
        $this->getJson($this->listUrl())->assertOk()->assertJsonPath("data.items.total", 0)
            ->assertJsonPath("data.evolution.series", []);

        DB::table("companies_sub_sections")->where("sub_section_id", 110)->update(["status" => "inactive"]);
        CompanySectionService::clearTenantCache();
        $this->getJson($this->listUrl())->assertForbidden();

    }

    public function test_invalid_dates_and_item_types_are_rejected(): void {

        $this->actingAs($this->admin);
        $this->getJson($this->listUrl(["from" => "2026-11-04", "type" => "unknown"]))
            ->assertUnprocessable()->assertJsonValidationErrors(["to", "type"]);
        $this->getJson($this->listUrl(["to" => "2028-01-01"]))
            ->assertUnprocessable()->assertJsonValidationErrors("to");
        $this->getJson($this->listUrl(["from" => "2026-02-30"]))
            ->assertUnprocessable()->assertJsonValidationErrors("from");

    }

    private function listUrl(array $filters = []): string {

        return route(self::ROUTE."list", $filters + ["from" => self::FROM, "to" => self::TO]);

    }

    private function item(string $code, string $type): int {

        return DB::table("items")->insertGetId([
            "internal_code" => $code,
            "name" => "Nombre actual de catálogo",
            "price" => 100,
            "currency_id" => $this->pen,
            "type" => $type,
            "status" => "inactive",
        ]);

    }

    private function sale(int $itemId, string $type, string $name, string $date, int $branchId, int $currencyId, string $total,
        string $saleStatus = "active", string $lineStatus = "active"): void {

        $saleId = DB::table("sales_header")->insertGetId([
            "serie_id" => DB::table("series")->where("branch_id", $branchId)->value("id"),
            "sequential" => ++$this->sequential,
            "holder_id" => $this->customerId,
            "seller_id" => $this->admin->id,
            "currency_id" => $currencyId,
            "issue_date" => $date,
            "total" => $total,
            "status" => $saleStatus,
        ]);
        DB::table("sales_body")->insert([
            "sale_header_id" => $saleId,
            "item_id" => $itemId,
            "currency_id" => $currencyId,
            "name" => $name,
            "quantity" => "1",
            "price" => $total,
            "total" => $total,
            "customer_id" => $this->customerId,
            "type" => $type,
            "extras" => "{}",
            "status" => $lineStatus,
        ]);

    }

}
