<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\System\Organizations\{User};
use App\Services\System\Essentials\{UserNavigationService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{Auth, DB, Schema, Storage};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class UserWorkspaceTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    private User $user;

    private UserNavigationService $navigationService;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

        DB::table("companies_sub_sections")
            ->where("company_id", 1)
            ->where("sub_section_id", DB::table("sub_sections")->where("dom_route", "sales.create")->value("id"))
            ->update(["status" => "active"]);
        CompanySectionService::clearTenantCache();

        $this->user = User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail();

        $this->navigationService = app(UserNavigationService::class);

    }

    public function test_visits_are_aggregated_by_user_and_route_without_event_rows(): void {

        $this->navigationService->record($this->user, "sales.create");
        $this->navigationService->record($this->user, "sales.create");
        $this->navigationService->record($this->user, "products.index");

        $newSaleId = DB::table("sub_sections")->where("dom_route", "sales.create")->value("id");

        $this->assertDatabaseCount("user_navigation_metrics", 2);
        $this->assertDatabaseHas("user_navigation_metrics", [
            "user_id" => $this->user->id,
            "sub_section_id" => $newSaleId,
            "visit_count" => 2,
        ]);

    }

    public function test_visit_counts_remain_aggregated_without_recent_rank(): void {

        $routes = DB::table("sub_sections as ss")
            ->join("companies_sub_sections as css", "css.sub_section_id", "=", "ss.id")
            ->where("css.company_id", 1)
            ->where("css.status", "active")
            ->where("ss.dom_route", "!=", "workspace.index")
            ->orderBy("ss.id")
            ->limit(11)
            ->pluck("ss.dom_route");

        foreach($routes as $routeName) {

            $this->navigationService->record($this->user, $routeName);

        }

        $metrics = DB::table("user_navigation_metrics")
            ->where("user_id", $this->user->id);

        $this->assertSame(11, (clone $metrics)->count());
        $this->assertFalse(Schema::hasColumn("user_navigation_metrics", "recent_rank"));

        $workspace = $this->navigationService->getWorkspace($this->user);

        $this->assertCount(6, $workspace["actions"]);
        $this->assertSame(6, $workspace["actions"]->pluck("id")->unique()->count());
        $this->assertTrue($workspace["actions"]->every(fn(array $item) => ($item["visit_count"] ?? 0) > 0));

    }

    public function test_revisiting_a_route_only_increments_its_visit_count(): void {

        $routes = DB::table("sub_sections as ss")
            ->join("companies_sub_sections as css", "css.sub_section_id", "=", "ss.id")
            ->where("css.company_id", 1)
            ->where("css.status", "active")
            ->where("ss.dom_route", "!=", "workspace.index")
            ->orderBy("ss.id")
            ->limit(10)
            ->pluck("ss.dom_route")
            ->values();

        foreach($routes as $routeName) {

            $this->navigationService->record($this->user, $routeName);

        }

        $this->navigationService->record($this->user, $routes[5]);

        $metrics = DB::table("user_navigation_metrics")
            ->where("user_id", $this->user->id)
            ->get(["sub_section_id", "visit_count"]);
        $revisitedId = DB::table("sub_sections")
            ->where("dom_route", $routes[5])
            ->value("id");

        $this->assertCount(10, $metrics);
        $this->assertSame(2, (int) $metrics->firstWhere("sub_section_id", $revisitedId)->visit_count);
        $this->assertSame(11, (int) $metrics->sum("visit_count"));

    }

    public function test_most_used_routes_still_follow_visit_counts(): void {

        $this->navigationService->record($this->user, "products.index");
        $this->navigationService->record($this->user, "sales.create");
        $this->navigationService->record($this->user, "sales.create");

        $workspace = $this->navigationService->getWorkspace($this->user);

        $this->assertArrayNotHasKey("recent", $workspace);
        $this->assertCount(6, $workspace["actions"]);
        $this->assertSame("Nueva venta", $workspace["actions"][0]["label"]);
        $this->assertSame(
            DB::table("sub_sections")->where("dom_route", "sales.create")->value("description"),
            $workspace["actions"][0]["description"]
        );
        $this->assertSame(2, $workspace["actions"][0]["visit_count"]);
        $this->assertSame("Productos", $workspace["actions"][1]["label"]);
        $this->assertSame(6, $workspace["actions"]->pluck("id")->unique()->count());
        $this->assertFalse(array_key_exists("visit_count", $workspace["actions"][2]));

        foreach($workspace["actions"] as $item) {

            $this->assertSame(
                DB::table("sub_sections")->where("id", $item["id"])->value("description"),
                $item["description"]
            );
            $this->assertSame(
                DB::table("sub_sections")->where("id", $item["id"])->value("dom_icon"),
                $item["icon"]
            );

        }

    }

    public function test_equally_used_actions_follow_the_navigation_order(): void {

        $serviceId = (int) DB::table("sub_sections")->where("dom_route", "service_sessions.index")->value("id");
        $customerId = (int) DB::table("sub_sections")->where("dom_route", "customers.index")->value("id");

        $this->assertGreaterThan($customerId, $serviceId);

        $this->navigationService->record($this->user, "customers.index");
        $this->navigationService->record($this->user, "service_sessions.index");

        $actions = $this->navigationService->getWorkspace($this->user)["actions"];

        $this->assertSame([$serviceId, $customerId], $actions->take(2)->pluck("id")->all());

    }

    public function test_workspace_uses_allowed_actions_when_the_user_has_no_visit_history(): void {

        $workspace = $this->navigationService->getWorkspace($this->user);

        $this->assertCount(6, $workspace["actions"]);
        $this->assertSame(6, $workspace["actions"]->pluck("id")->unique()->count());
        $this->assertTrue($workspace["actions"]->every(fn(array $item) => !array_key_exists("visit_count", $item)));

    }

    public function test_workspace_is_the_authenticated_landing_page_and_renders_recommendations(): void {

        $this->navigationService->record($this->user, "sales.create");
        Auth::login($this->user);
        $this->travelTo(Carbon::parse("2026-10-25 09:00:00", config("app.timezone")));

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ]);

        $page = $this->get(route("workspace.index"));
        $page->assertOk()->assertSee('<div id="app"></div>', false);
        $page->assertDontSee('class="br-workspace__layout"', false);
        $page->assertSee('class="br-navbar-brand"', false);
        $page->assertSee(
            '<span class="br-navbar-user__company" title="EMPRESA DE PRUEBAS S.A.C.">EMPRESA DE PRUEBAS S.A.C.</span>',
            false
        );
        $page->assertSee(
            'src="'.asset(config("app.owner_app")->assets->img->combinationmark).'" alt="Marca combinada de Blapos"',
            false
        );

        $response = $this->getJson(route("workspace.index"));
        $response->assertOk()
            ->assertJsonPath("first_name", "Administrador")
            ->assertJsonPath("breadcrumb.section", "Inicio")
            ->assertJsonPath("breadcrumb.page", "Mi espacio")
            ->assertJsonPath("company.name", "EMPRESA DE PRUEBAS S.A.C.")
            ->assertJsonPath("company.commercial_name", "Empresa de pruebas")
            ->assertJsonPath("company.document_type", "RUC")
            ->assertJsonPath("company.document_number", "20999999999")
            ->assertJsonPath("support.email", "gianfranco_29_01@hotmail.com")
            ->assertJsonPath("support.phone_href", "+51987057624");

        $actions = $response->json("actions");
        $this->assertCount(6, $actions);
        $this->assertSame("Ventas", $actions[0]["world"]);
        $this->assertNotSame($actions[0]["world"], $actions[0]["description"]);
        $this->assertContains("Nueva venta", array_column($actions, "label"));
        $this->assertSame(asset(config("app.owner_app")->assets->img->logomark), $response->json("company.logomark_url"));
        $this->assertSame("/workspace", \App\Providers\RouteServiceProvider::HOME);

    }

    public function test_workspace_heading_uses_only_the_first_name_without_a_time_of_day_greeting(): void {

        $this->user->update(["name" => "ANA PAULA Torres"]);
        Auth::login($this->user);

        foreach(["09:00:00", "15:00:00", "21:00:00"] as $time) {

            $this->travelTo(Carbon::parse("2026-10-25 ".$time, config("app.timezone")));

            $this->withoutMiddleware([
                \App\Http\Middleware\ResolveTenant::class,
                \App\Http\Middleware\TrustHosts::class,
                \App\Http\Middleware\EnsureTenantSession::class,
                \App\Http\Middleware\EnsureAuthenticatedSession::class,
                \App\Http\Middleware\EnsureModulePermission::class,
                \App\Http\Middleware\EnsureOperationalScope::class,
            ])->getJson(route("workspace.index"))
                ->assertOk()
                ->assertJsonPath("first_name", "ANA");

        }

        $this->travelBack();

    }

    public function test_workspace_excludes_disabled_modules_from_accesses(): void {

        $productsId = DB::table("sub_sections")->where("dom_route", "products.index")->value("id");

        DB::table("companies_sub_sections")
            ->where("sub_section_id", $productsId)
            ->update(["status" => "inactive"]);
        CompanySectionService::clearCache((int) $this->user->role_id);

        try {

            $workspace = $this->navigationService->getWorkspace($this->user);

            Auth::login($this->user);
            $response = $this->withoutMiddleware([
                \App\Http\Middleware\ResolveTenant::class,
                \App\Http\Middleware\TrustHosts::class,
                \App\Http\Middleware\EnsureTenantSession::class,
                \App\Http\Middleware\EnsureAuthenticatedSession::class,
                \App\Http\Middleware\EnsureModulePermission::class,
                \App\Http\Middleware\EnsureOperationalScope::class,
            ])->getJson(route("workspace.index"));

            $response->assertOk();
            $this->assertNotContains($productsId, $workspace["actions"]->pluck("id"));
            $this->assertSame("Empresa de pruebas", $response->json("company.commercial_name"));

        }finally {

            CompanySectionService::clearCache((int) $this->user->role_id);

        }

    }

    public function test_workspace_displays_the_company_logomark_when_it_exists(): void {

        Storage::fake("public");
        $logoPath = "tenants/tests/branding/logomark.png";
        Storage::disk("public")->put(
            $logoPath,
            base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII=")
        );

        $company = app(TenantCompanyContext::class)->get();
        $company->logomark = $logoPath;
        $company->save();
        Auth::login($this->user);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->getJson(route("workspace.index"));

        $response->assertOk();
        $response->assertJsonPath("company.logomark_url", asset("storage/".$logoPath));

    }

    public function test_workspace_shows_company_contacts_and_copy_controls(): void {

        $company = app(TenantCompanyContext::class)->get();
        $company->update([
            "email" => "contacto@empresa.test",
            "telephone" => "999888777",
        ]);

        foreach([
            "facebook" => "https://facebook.com/empresa",
            "instagram" => "https://instagram.com/empresa",
            "whatsapp" => "https://wa.me/51999888777",
        ] as $type => $link) {

            $company->socialsMedia()->create(compact("type", "link"));

        }

        Auth::login($this->user);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->getJson(route("workspace.index"));

        $response->assertOk();
        $response->assertJsonPath("company.document_number", "20999999999")
            ->assertJsonPath("company.email", "contacto@empresa.test")
            ->assertJsonPath("company.socials.facebook", "https://facebook.com/empresa")
            ->assertJsonPath("company.socials.instagram", "https://instagram.com/empresa")
            ->assertJsonPath("company.socials.whatsapp", "https://wa.me/51999888777");

    }

    public function test_workspace_shows_short_placeholders_for_missing_company_contacts(): void {

        $company = app(TenantCompanyContext::class)->get();
        $company->update([
            "email" => null,
            "telephone" => null,
            "address" => null,
        ]);
        Auth::login($this->user);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->getJson(route("workspace.index"));

        $response->assertOk();
        $response->assertJsonPath("company.email", null)
            ->assertJsonPath("company.telephone", null)
            ->assertJsonPath("company.address", null);

    }

    public function test_authenticated_user_can_only_update_personal_account_fields(): void {

        Auth::login($this->user);
        $originalRoleId = $this->user->role_id;

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->patch(route("account.update"), [
            "name" => "Administrador actualizado",
            "email" => "cuenta.actualizada@example.test",
            "phone_number" => "999888777",
            "gender" => "other",
            "birthdate" => "1990-05-12",
            "role_id" => null,
            "status" => "blocked",
        ]);

        $response->assertRedirect(route("account.index"));
        $response->assertSessionHas("status");

        $this->user->refresh();

        $this->assertSame("Administrador actualizado", $this->user->name);
        $this->assertSame("cuenta.actualizada@example.test", $this->user->email);
        $this->assertSame("999888777", $this->user->phone_number);
        $this->assertSame($originalRoleId, $this->user->role_id);
        $this->assertSame("active", $this->user->status);

    }

    public function test_http_navigation_records_new_sale_without_confusing_it_with_pos(): void {

        Auth::login($this->user);

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("sales.create"))->assertOk();

        $newSaleId = DB::table("sub_sections")->where("dom_route", "sales.create")->value("id");
        $posId = DB::table("sub_sections")->where("dom_route", "sales.pos")->value("id");

        $this->assertDatabaseHas("user_navigation_metrics", [
            "user_id" => $this->user->id,
            "sub_section_id" => $newSaleId,
            "visit_count" => 1,
        ]);

        $this->assertDatabaseMissing("user_navigation_metrics", [
            "user_id" => $this->user->id,
            "sub_section_id" => $posId,
        ]);

    }

}
