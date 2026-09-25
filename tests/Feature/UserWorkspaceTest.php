<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\System\Organizations\{User};
use App\Services\System\Essentials\{UserNavigationService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{Auth, DB};
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
        $this->navigationService->record($this->user, "home.index");

        $newSaleId = DB::table("sub_sections")->where("dom_route", "sales.create")->value("id");

        $this->assertDatabaseCount("user_navigation_metrics", 2);
        $this->assertDatabaseHas("user_navigation_metrics", [
            "user_id" => $this->user->id,
            "sub_section_id" => $newSaleId,
            "visit_count" => 2,
            "recent_rank" => 2,
        ]);

    }

    public function test_only_ten_routes_keep_a_recent_rank_while_counts_remain_aggregated(): void {

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
        $this->assertSame(10, (clone $metrics)->whereNotNull("recent_rank")->count());
        $this->assertSame(10, (int) (clone $metrics)->max("recent_rank"));

    }

    public function test_revisiting_a_recent_route_keeps_the_ranks_consecutive(): void {

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

        $ranks = DB::table("user_navigation_metrics")
            ->where("user_id", $this->user->id)
            ->whereNotNull("recent_rank")
            ->orderBy("recent_rank")
            ->pluck("recent_rank")
            ->map(fn($rank) => (int) $rank)
            ->all();

        $this->assertSame(range(1, 10), $ranks);

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
        $response->assertSee("Mi espacio de trabajo");
        $response->assertSee("Vistos recientemente");
        $response->assertSee("Más utilizados");
        $response->assertSee("Nueva venta");
        $response->assertDontSee("Dashboard");
        $this->assertSame("/workspace", \App\Providers\RouteServiceProvider::HOME);

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
            "recent_rank" => 1,
        ]);

        $this->assertDatabaseMissing("user_navigation_metrics", [
            "user_id" => $this->user->id,
            "sub_section_id" => $posId,
        ]);

    }
}
