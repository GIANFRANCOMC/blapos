<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\System\Organizations\{User};
use App\Services\System\Essentials\{UserNavigationService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
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
        $this->assertSame("Nueva venta", $workspace["popular"][0]["label"]);
        $this->assertSame(2, $workspace["popular"][0]["visit_count"]);
        $this->assertEmpty($workspace["popular"]->pluck("id")->intersect($workspace["suggested"]->pluck("id")));

    }

    public function test_workspace_is_the_authenticated_landing_page_and_renders_recommendations(): void {

        $this->navigationService->record($this->user, "sales.create");
        Auth::login($this->user);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("workspace.index"));

        $response->assertOk();
        $response->assertSee("¡Hola, Administrador!");
        $response->assertDontSee("Accede a tus funciones habituales o explora otras disponibles para tu perfil.");
        $response->assertDontSee("Vistos recientemente");
        $response->assertSee("Más utilizados");
        $response->assertSee("También puedes usar");
        $response->assertSee("Nueva venta");
        $response->assertSee("Empresa de pruebas");
        $response->assertSee("20999999999");
        $response->assertSee("Tipo de documento");
        $response->assertSee("Número de documento");
        $response->assertSee('<span class="br-workspace__company-text">RUC</span>', false);
        $response->assertDontSee("Oferta comercial");
        $response->assertDontSee("1 visita");
        $response->assertDontSee("Dashboard");
        $workspace = $response->viewData("workspace");

        $this->assertSame("Ventas", $workspace["popular"][0]["world"]);
        $this->assertNotEmpty($workspace["suggested"][0]["world"]);
        $this->assertSame(
            asset(config("app.owner_app")->assets->img->combinationmark),
            $response->viewData("logoUrl")
        );
        $response->assertSee('alt="Logo de Empresa de pruebas"', false);
        $this->assertEmpty($workspace["popular"]->pluck("id")->intersect($workspace["suggested"]->pluck("id")));
        $this->assertSame(
            $workspace["popular"]->count() + $workspace["suggested"]->count(),
            substr_count($response->getContent(), 'class="br-workspace__link"')
        );
        $response->assertSeeInOrder([
            "<dt>Correo</dt>",
            "<dt>Dirección</dt>",
            "<dt>Teléfono</dt>",
            "<dt>WhatsApp</dt>",
        ], false);
        $this->assertSame("/workspace", \App\Providers\RouteServiceProvider::HOME);

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
            ])->get(route("workspace.index"));

            $response->assertOk();
            $this->assertNotContains($productsId, $workspace["popular"]->pluck("id"));
            $this->assertNotContains($productsId, $workspace["suggested"]->pluck("id"));
            $this->assertSame("Empresa de pruebas", $response->viewData("company")->commercial_name);

        }finally {

            CompanySectionService::clearCache((int) $this->user->role_id);

        }

    }

    public function test_workspace_displays_the_company_logo_when_it_exists(): void {

        Storage::fake("public");
        $logoPath = "tenants/tests/branding/combinationmark.png";
        Storage::disk("public")->put(
            $logoPath,
            base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lXcAAAAASUVORK5CYII=")
        );

        $company = app(TenantCompanyContext::class)->get();
        $company->combinationmark = $logoPath;
        $company->save();
        Auth::login($this->user);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("workspace.index"));

        $response->assertOk();
        $this->assertSame(asset("storage/".$logoPath), $response->viewData("logoUrl"));
        $response->assertSee('alt="Logo de Empresa de pruebas"', false);

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
        ])->get(route("workspace.index"));

        $response->assertOk();
        $response->assertSee('data-br-copy-value="20999999999"', false);
        $response->assertSee('data-br-copy-value="contacto@empresa.test"', false);
        $response->assertSee("https://facebook.com/empresa");
        $response->assertSee("https://instagram.com/empresa");
        $response->assertSee("https://wa.me/51999888777");

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
        ])->get(route("workspace.index"));

        $response->assertOk();
        $response->assertSee("Facebook");
        $response->assertSee("Instagram");
        $response->assertSee("WhatsApp");
        $response->assertSee("Sin información");
        $response->assertDontSee('data-br-copy-value=""', false);
        $response->assertDontSee('aria-label="Copiar correo"', false);

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
