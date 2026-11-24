<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureOperationalScope, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Organizations\{User};
use App\Services\System\General\{AutomaticMasterDataService};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{Auth, DB};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class AutomaticMasterDataTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

        Auth::login(User::query()->where("email", "admin@example.test")->firstOrFail());

        $this->withoutMiddleware([
            ResolveTenant::class,
            TrustHosts::class,
            EnsureTenantSession::class,
            EnsureAuthenticatedSession::class,
            EnsureOperationalScope::class,
        ]);

    }

    public function test_fingerprint_setting_follows_the_tenant_module_without_persisting_a_manual_value(): void {

        $key = AutomaticMasterDataService::CUSTOMER_FINGERPRINT_REGISTRATION;
        $moduleId = (int) DB::table("sub_sections")
            ->where("dom_route", "tracking_attendances.index")
            ->value("id");

        $this->setModuleStatus($moduleId, "inactive");
        $this->assertFalse(AutomaticMasterDataService::enabled($key));

        $this->getJson(route("master_data.list", ["resource" => "automatic"]))
            ->assertOk()
            ->assertJsonPath("data.0.code", $key)
            ->assertJsonPath("data.0.value", false);

        $this->postJson(route("master_data.store", ["resource" => "automatic"]), [
            "code" => $key,
            "name" => "Valor manual",
            "status" => "active",
        ])->assertForbidden();

        $this->getJson(route("customers.initParams"))
            ->assertOk()
            ->assertJsonPath("config.automatic.{$key}", false)
            ->assertJsonPath("config.biometricDevices.records", []);

        $this->postJson(route("customers.registerBiometricFingerprint", ["id" => 1]), [])
            ->assertForbidden();

        $this->setModuleStatus($moduleId, "active");

        $this->assertTrue(AutomaticMasterDataService::enabled($key));

        $this->getJson(route("master_data.list", ["resource" => "automatic"]))
            ->assertOk()
            ->assertJsonPath("data.0.value", true);

        $this->getJson(route("customers.initParams"))
            ->assertOk()
            ->assertJsonPath("config.automatic.{$key}", true);

        $this->postJson(route("customers.registerBiometricFingerprint", ["id" => 1]), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(["biometric_device_id"]);

        $this->setModuleStatus($moduleId, "inactive");

        $this->assertFalse(AutomaticMasterDataService::enabled($key));

    }

    public function test_favorites_follow_the_tenant_module_and_disappear_when_it_is_disabled(): void {

        $key = AutomaticMasterDataService::MENU_FAVORITES;
        $moduleId = (int) DB::table("sub_sections")
            ->where("dom_route", "favorites.index")
            ->value("id");

        $this->setModuleStatus($moduleId, "inactive");
        $this->assertFalse(AutomaticMasterDataService::enabled($key));
        $this->get(route("home.index"))
            ->assertOk()
            ->assertDontSee('id="brFabFavoritesToggle"', false);
        $this->get(route("favorites.index"))->assertForbidden();

        $this->setModuleStatus($moduleId, "active");

        $this->assertTrue(AutomaticMasterDataService::enabled($key));
        $this->assertTrue(collect(AutomaticMasterDataService::list())
            ->firstWhere("code", $key)["value"]);

        $this->get(route("home.index"))
            ->assertOk()
            ->assertSee('id="brFabFavoritesToggle"', false);
        $this->get(route("favorites.index"))
            ->assertOk()
            ->assertSee('data-navigation-world="Configuración"', false)
            ->assertSee('data-navigation-group="Preferencias"', false);

        $this->setModuleStatus($moduleId, "inactive");

        $this->assertFalse(AutomaticMasterDataService::enabled($key));
        $this->assertFalse(RolePermissionService::canAccessRoute(Auth::user(), "favorites.index"));
        $this->assertFalse(collect(AutomaticMasterDataService::list())
            ->firstWhere("code", $key)["value"]);

        $this->get(route("home.index"))
            ->assertOk()
            ->assertDontSee('id="brFabFavoritesToggle"', false)
            ->assertDontSee('id="brFabFavoritesPanel"', false);
        $this->get(route("favorites.index"))->assertForbidden();
        $this->getJson(route("favorites.initParams"))->assertForbidden();
        $this->patchJson(route("favorites.update", ["id" => 0]), [])->assertForbidden();

    }

    public function test_favorites_accept_analytics_without_adding_home_or_the_preferences_page(): void {

        $modules = DB::table("sub_sections")
            ->whereIn("dom_route", ["home.index", "favorites.index", "dashboard.index"])
            ->pluck("id", "dom_route");
        $preference = [
            "show_actions" => true,
            "show_only_favorites" => false,
            "show_descriptions" => true,
            "is_favorite" => true,
        ];

        $this->patchJson(route("favorites.update", $modules["dashboard.index"]), $preference)
            ->assertSuccessful();

        foreach(["home.index", "favorites.index"] as $route) {
            $this->patchJson(route("favorites.update", $modules[$route]), $preference)
                ->assertForbidden();
        }

    }

    private function setModuleStatus(int $moduleId, string $status): void {

        $this->assertGreaterThan(0, $moduleId);
        DB::table("companies_sub_sections")
            ->where("company_id", 1)
            ->where("sub_section_id", $moduleId)
            ->update(["status" => $status]);
        CompanySectionService::clearTenantCache();

    }

}
