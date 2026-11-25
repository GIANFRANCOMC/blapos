<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Organizations\{User};
use App\Services\System\Organizations\{AccessScopeService};
use App\Services\System\Organizations\Branches\{SerieService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class DashboardBranchScopeTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    private const DATE = "2026-11-25";

    public function test_options_and_aggregates_follow_the_authenticated_branch_scope(): void {
        $this->provisionSystemDatabase();
        $this->withoutMiddleware([ResolveTenant::class, TrustHosts::class, EnsureTenantSession::class, EnsureAuthenticatedSession::class]);

        $admin = User::query()->where("email", "admin@example.test")->firstOrFail();
        $branchId = (int) DB::table("branches")->where("name", "Sede Principal")->value("id");
        $otherBranchId = DB::table("branches")->insertGetId([
            "company_id" => 1,
            "internal_code" => "SUC-RESUMEN",
            "name" => "Sede secundaria",
            "status" => "active",
        ]);
        SerieService::createForBranch($otherBranchId);
        $customerId = DB::table("customers")->insertGetId([
            "identity_document_type_id" => DB::table("identity_document_types")->where("code", "dni")->value("id"),
            "document_number" => "70000293",
            "name" => "Cliente de resumen",
            "status" => "active",
        ]);
        $currencyId = DB::table("currencies")->where("code", "PEN")->value("id");
        foreach([$branchId, $otherBranchId] as $offset => $branch) {
            DB::table("sales_header")->insert([
                "serie_id" => DB::table("series")->where("branch_id", $branch)->value("id"),
                "sequential" => 99200 + $offset,
                "holder_id" => $customerId,
                "seller_id" => $admin->id,
                "currency_id" => $currencyId,
                "issue_date" => self::DATE . " 10:00:00",
                "total" => "15.00",
                "status" => "active",
            ]);
        }

        $optionsUrl = route("dashboard.initParams", ["page" => "main"]);
        $dataUrl = route("dashboard.initData", ["date" => self::DATE]);
        $this->actingAs($admin)->getJson($optionsUrl)->assertOk()->assertJsonCount(2, "branches");
        $this->getJson($dataUrl)->assertOk()->assertJsonPath("data.sales.net.count", 2);

        $roleId = DB::table("roles")->insertGetId([
            "slug" => "resumen-reader",
            "name" => "Perfil de resumen",
            "is_full_access" => false,
            "branch_scope_mode" => "restricted",
            "status" => "active",
        ]);
        DB::table("role_branches")->insert(["role_id" => $roleId, "branch_id" => $branchId]);
        $dashboardId = DB::table("sub_sections")->where("dom_route", "dashboard.index")->value("id");
        DB::table("role_sub_sections")->insert([
            "role_id" => $roleId, "sub_section_id" => $dashboardId, "actions" => '["view"]', "status" => "active",
        ]);
        $admin->update(["role_id" => $roleId]);
        AccessScopeService::clearUserCache($admin->id);
        $this->actingAs($admin->fresh());

        $this->getJson($optionsUrl)->assertOk()->assertJsonCount(1, "branches")->assertJsonPath("branches.0.id", $branchId);
        $this->getJson($dataUrl)->assertOk()
            ->assertJsonPath("data.sales.net.count", 1)
            ->assertJsonPath("data.branches.active_count", 1);
        $this->getJson($dataUrl . "&branch_id=" . $branchId)->assertOk()
            ->assertJsonPath("data.branch_id", $branchId)->assertJsonPath("data.sales.net.count", 1);
        $this->getJson($dataUrl . "&branch_id=" . $otherBranchId)->assertForbidden();

        DB::table("role_branches")->where("role_id", $roleId)->delete();
        AccessScopeService::clearUserCache($admin->id);
        $this->getJson($optionsUrl)->assertOk()->assertJsonCount(0, "branches");
        $this->getJson($dataUrl)->assertOk()
            ->assertJsonPath("data.sales.net.count", 0)
            ->assertJsonPath("data.branches.active_count", 0);
    }
}
