<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\System\Catalogs\Categories\{CategoryItemService};
use App\Services\System\Database\{SystemCatalogSyncService};
use App\Services\System\Organizations\Branches\{SerieService};
use App\Services\System\Organizations\Companies\{CompanyProvisioningService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use App\Services\System\Warehouses\Warehouses\{WarehouseItemService};
use Database\Seeders\{SystemNavigationSeeder};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Support\Facades\{Auth, DB, Hash, Route};
use RuntimeException;
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class SystemNavigationProvisioningTest extends TestCase {

    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        $this->provisionSystemDatabase();

    }

    public function test_world_names_match_their_navigation_content(): void {

        $this->assertSame([
            "sc_workspace" => "Inicio",
            "sc_operations" => "Operaciones y clientes",
            "sc_sales" => "Ventas",
            "sc_purchases" => "Compras",
            "sc_cash" => "Caja",
            "sc_inventory" => "Inventario",
            "sc_items" => "Catálogo comercial",
            "sc_people" => "Equipo y activos",
            "sc_analytics" => "Analítica",
            "sc_configuration" => "Configuración",
        ], DB::table("sections")
            ->join("menu_categories", "menu_categories.id", "=", "sections.menu_category_id")
            ->orderBy("menu_categories.order")
            ->orderBy("sections.order")
            ->pluck("sections.dom_label", "sections.slug")
            ->all());

    }

    public function test_all_enabled_module_screen_routes_render_the_tenant_shell(): void {

        $this->withoutVite();
        $this->withoutMiddleware([
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\ShareTenantAnnouncements::class,
        ]);
        $admin = \App\Models\System\Organizations\User::query()->where("email", "admin@example.test")->firstOrFail();
        $routes = DB::table("sub_sections")->where("status", "active")->orderBy("id")->pluck("dom_route");

        $this->actingAs($admin);
        $this->assertCount(52, $routes);
        foreach ($routes as $routeName) {
            $screen = $this->get(route($routeName));
            $this->assertSame(200, $screen->getStatusCode(), $routeName);
            $screen->assertSee('class="fg-topbar"', false);
        }

    }

    public function test_every_submodule_has_its_own_icon_in_the_catalog(): void {

        $this->assertSame(0, DB::table("sub_sections")->where("dom_icon", "")->count());
        $this->assertSame("fa-solid fa-users", DB::table("sub_sections")->where("dom_route", "customers.index")->value("dom_icon"));
        $this->assertSame("fa-solid fa-cube", DB::table("sub_sections")->where("dom_route", "stocks_management.stock.index")->value("dom_icon"));

    }

    public function test_world_summaries_and_group_icons_are_stored_in_the_navigation_catalog(): void {

        $this->assertSame("Accede a tus funciones frecuentes.", DB::table("sections")->where("slug", "sc_workspace")->value("description"));
        $this->assertSame(0, DB::table("sections")->whereNull("description")->orWhere("description", "")->count());
        $this->assertSame("fa-regular fa-user", DB::table("menu_groups")->where("id", 23)->value("dom_icon"));
        $this->assertSame(0, DB::table("menu_groups")->where("dom_icon", "")->count());

    }

    public function test_group_names_describe_their_pages_without_repeating_world_names(): void {

        $this->assertSame([
            1 => "Registro de ventas",
            5 => "Registro de compras",
            7 => "Operación de caja",
            8 => "Resumen y gastos",
            9 => "Servicios y asistencias",
            11 => "Gestión de clientes",
            13 => "Oferta comercial",
            19 => "Activos",
        ], DB::table("menu_groups")
            ->whereIn("id", [1, 5, 7, 8, 9, 11, 13, 19])
            ->orderBy("id")
            ->pluck("name", "id")
            ->all());

    }

    public function test_daily_operations_appear_before_customer_administration(): void {

        $this->assertDatabaseMissing("menu_groups", ["section_id" => 1]);

        $this->assertSame(
            ["Servicios y asistencias", "Gestión de clientes", "Atención al cliente"],
            DB::table("menu_groups")
                ->where("section_id", 2)
                ->orderBy("order")
                ->pluck("name")
                ->all()
        );

        $this->assertSame(
            [
                "service_sessions.index",
                "tracking_attendances.index",
                "customers.index",
                "tracking_subscriptions.index",
                "tracking_customers.index",
                "tracking_notifications.index",
                "book_complaints.index",
            ],
            DB::table("companies_sub_sections as company_modules")
                ->join("sub_sections as modules", "modules.id", "=", "company_modules.sub_section_id")
                ->where("company_modules.company_id", 1)
                ->where("modules.section_id", 2)
                ->orderBy("company_modules.sub_section_order")
                ->pluck("modules.dom_route")
                ->all()
        );

    }

    public function test_new_company_uses_catalog_defaults_and_keeps_the_admin_role_complete(): void {

        $catalogCount = DB::table("sub_sections")->where("status", "active")->count();
        $companyCount = DB::table("companies_sub_sections")
            ->where("company_id", 1)
            ->where("status", "active")
            ->count();

        $adminRoleId = DB::table("roles")
            ->where("is_full_access", true)
            ->value("id");

        $adminCount = DB::table("role_sub_sections")
            ->where("role_id", $adminRoleId)
            ->where("status", "active")
            ->count();

        $this->assertSame(5, DB::table("roles")
            ->whereIn("slug", ["vendedor", "compras", "gestion", "auditoria", "contador"])
            ->where("is_full_access", false)
            ->where("status", "active")
            ->count());

        $this->assertSame(52, $catalogCount);
        $this->assertSame($catalogCount, $companyCount);
        $this->assertSame($catalogCount, $adminCount);

    }

    public function test_every_catalog_module_is_enabled_by_default_with_a_registered_route(): void {

        $modules = DB::table("sub_sections")->where("status", "active")->get();
        $this->assertNotEmpty($modules);
        $this->assertDatabaseHas("sub_sections", ["dom_route" => "sales.pos", "dom_label" => "POS venta"]);
        foreach($modules as $module) {
            $this->assertTrue(Route::has($module->dom_route), $module->dom_route);
            $this->assertTrue((bool) $module->is_enabled_by_default, $module->dom_route);
        }

    }

    public function test_legacy_navigation_migration_preserves_existing_module_preferences(): void {

        $moduleId = DB::table("sub_sections")->where("dom_route", "sales.index")->value("id");
        DB::table("companies_sub_sections")->where("sub_section_id", $moduleId)->update(["status" => "inactive"]);

        $migration = require database_path("migrations/2026_11_11_000001_enable_sales_navigation.php");
        $migration->up();
        $migration->down();

        $this->assertDatabaseHas("companies_sub_sections", ["sub_section_id" => $moduleId, "status" => "inactive"]);

    }

    public function test_root_company_provisioning_is_idempotent_without_a_company_selector(): void {

        $tables = [
            "company_settings",
            "misc_expense_categories",
            "payment_methods",
            "payment_method_variants",
            "roles",
            "role_sub_sections",
            "users",
        ];

        $before = collect($tables)
            ->mapWithKeys(fn($table) => [$table => DB::table($table)->count()])
            ->all();

        $provisioning = app(CompanyProvisioningService::class);
        $provisioning->enable();
        $provisioning->ensureAdminUser(
            "Administrador de pruebas",
            "admin@example.test",
            "password"
        );

        $after = collect($tables)
            ->mapWithKeys(fn($table) => [$table => DB::table($table)->count()])
            ->all();

        $this->assertSame($before, $after);

    }

    public function test_reprovisioning_does_not_reset_the_initial_administrator(): void {

        $admin = DB::table("users")->where("email", "admin@example.test")->first();
        $passwordHash = Hash::make("changed-password");

        DB::table("users")
            ->where("id", $admin->id)
            ->update([
                "name" => "Administrador personalizado",
                "password" => $passwordHash,
                "status" => "inactive",
            ]);

        DB::table("user_branches")
            ->where("user_id", $admin->id)
            ->update(["status" => "inactive"]);

        $resolvedId = app(CompanyProvisioningService::class)->ensureAdminUser(
            "Administrador inicial",
            "admin@example.test",
            "initial-password"
        );

        $this->assertSame((int) $admin->id, $resolvedId);
        $this->assertDatabaseHas("users", [
            "id" => $admin->id,
            "name" => "Administrador personalizado",
            "password" => $passwordHash,
            "status" => "inactive",
        ]);

        $this->assertDatabaseHas("user_branches", [
            "user_id" => $admin->id,
            "status" => "inactive",
        ]);

    }

    public function test_reprovisioning_does_not_reset_an_issued_document_series(): void {

        $series = DB::table("series")->first();

        DB::table("series")
            ->where("id", $series->id)
            ->update([
                "number" => 24,
                "init" => 318,
                "status" => "inactive",
            ]);

        app(CompanyProvisioningService::class)->enable();

        $this->assertDatabaseHas("series", [
            "id" => $series->id,
            "number" => 24,
            "init" => 318,
            "status" => "inactive",
        ]);

        $this->assertSame(
            1,
            DB::table("series")
                ->where("branch_id", $series->branch_id)
                ->where("document_type_id", $series->document_type_id)
                ->count()
        );

    }

    public function test_reprovisioning_preserves_tenant_preferences_and_restores_missing_defaults(): void {

        $currencyId = (int) DB::table("currencies")->insertGetId([
            "code" => "USD",
            "sign" => "$",
            "singular_name" => "DÓLAR",
            "plural_name" => "DÓLARES",
            "status" => "active",
        ]);

        DB::table("companies")
            ->where("id", 1)
            ->update(["currency_id" => $currencyId]);

        DB::table("company_settings")
            ->where("company_id", 1)
            ->where("group", "inventory")
            ->where("key", "allow_negative_stock_on_sale")
            ->update(["value" => "true"]);

        DB::table("company_settings")
            ->where("company_id", 1)
            ->where("group", "inventory")
            ->where("key", "restore_stock_on_sale_cancellation")
            ->delete();

        DB::table("taxes")
            ->where("code", "SALE-ICBP")
            ->update(["rate" => 0.75]);

        DB::table("payment_methods")
            ->where("code", "CASH")
            ->update(["status" => "inactive"]);

        app(CompanyProvisioningService::class)->enable();

        $this->assertDatabaseHas("company_settings", [
            "company_id" => 1,
            "group" => "inventory",
            "key" => "allow_negative_stock_on_sale",
            "value" => "true",
        ]);

        $this->assertDatabaseHas("company_settings", [
            "company_id" => 1,
            "group" => "inventory",
            "key" => "restore_stock_on_sale_cancellation",
            "value" => "false",
        ]);

        $this->assertDatabaseHas("taxes", [
            "code" => "SALE-ICBP",
            "rate" => 0.75,
        ]);

        $this->assertDatabaseHas("payment_methods", [
            "code" => "CASH",
            "status" => "inactive",
        ]);

        $this->assertDatabaseHas("companies", [
            "id" => 1,
            "currency_id" => $currencyId,
        ]);

    }

    public function test_reprovisioning_company_metadata_does_not_reactivate_an_inactive_company(): void {

        DB::table("companies")->where("id", 1)->update(["status" => "inactive"]);

        app(CompanyProvisioningService::class)->createOrUpdate([
            "slug" => "tests",
            "commercial_name" => "Empresa renombrada",
            "legal_name" => "EMPRESA DE PRUEBAS S.A.C.",
            "document_number" => "20999999999",
            "email" => "admin@example.test",
        ]);

        $this->assertDatabaseHas("companies", [
            "id" => 1,
            "commercial_name" => "Empresa renombrada",
            "status" => "inactive",
        ]);

    }

    public function test_reprovisioning_preserves_operational_resources_after_renaming_the_branch(): void {

        $branchId = (int) DB::table("branches")
            ->where("internal_code", "SUC-PRINCIPAL")
            ->value("id");

        DB::table("branches")
            ->where("id", $branchId)
            ->update(["name" => "Sede Lima"]);

        DB::table("warehouses")
            ->where("branch_id", $branchId)
            ->update(["status" => "inactive"]);

        DB::table("cash_registers")
            ->where("branch_id", $branchId)
            ->update(["status" => "inactive"]);

        DB::table("customers")
            ->where("document_number", "999999999")
            ->update(["status" => "inactive"]);

        $provisioning = app(CompanyProvisioningService::class);
        $provisioning->enable();
        $adminId = $provisioning->ensureAdminUser(
            "Administrador de pruebas",
            "admin@example.test",
            "password"
        );

        $this->assertDatabaseHas("branches", [
            "id" => $branchId,
            "name" => "Sede Lima",
        ]);

        $this->assertSame(1, DB::table("branches")->count());
        $this->assertDatabaseHas("warehouses", ["branch_id" => $branchId, "status" => "inactive"]);
        $this->assertDatabaseHas("cash_registers", ["branch_id" => $branchId, "status" => "inactive"]);
        $this->assertDatabaseHas("customers", ["document_number" => "999999999", "status" => "inactive"]);
        $this->assertDatabaseHas("user_branches", ["user_id" => $adminId, "branch_id" => $branchId]);

    }

    public function test_series_gaps_are_detected_across_multiple_series(): void {

        $series = DB::table("series")->orderBy("id")->limit(2)->get();
        $customerId = (int) DB::table("customers")->value("id");
        $sellerId = (int) DB::table("users")->value("id");
        $currencyId = (int) DB::table("currencies")->value("id");

        foreach([[1, 3], [7, 8]] as $index => $numbers) {

            foreach($numbers as $number) {

                $saleId = (int) DB::table("sales_header")->insertGetId([
                    "serie_id" => $series[$index]->id,
                    "sequential" => $number,
                    "holder_id" => $customerId,
                    "seller_id" => $sellerId,
                    "currency_id" => $currencyId,
                    "issue_date" => now()->toDateString(),
                    "total" => 1,
                ]);

                DB::table("series_correlative_movements")->insert([
                    "serie_id" => $series[$index]->id,
                    "sale_header_id" => $saleId,
                    "sequential" => $number,
                    "action" => "issued",
                    "source" => "sale",
                ]);

            }

        }

        $this->assertSame([
            [
                "serie_id" => $series[0]->id,
                "serie" => $series[0]->code.$series[0]->number,
                "first" => 1,
                "last" => 3,
                "missing" => [2],
            ],
        ], SerieService::detectGaps());

        $this->assertSame(
            [],
            SerieService::detectGaps((int) $series[1]->branch_id + 1000)
        );

    }

    public function test_catalog_sync_preserves_existing_choices_and_inserts_missing_role_permissions(): void {

        $subSectionId = (int) DB::table("sub_sections")
            ->where("dom_route", "sales.create")
            ->value("id");

        $roleId = (int) DB::table("roles")
            ->where("is_full_access", true)
            ->value("id");
        $existingRoleModuleId = (int) DB::table("sub_sections")
            ->where("dom_route", "sales.index")
            ->value("id");

        DB::table("companies_sub_sections")
            ->where("company_id", 1)
            ->where("sub_section_id", $subSectionId)
            ->update(["status" => "inactive", "section_order" => 900, "sub_section_order" => 901]);

        DB::table("companies_sub_sections")
            ->where("company_id", 1)
            ->where("sub_section_id", $existingRoleModuleId)
            ->delete();

        DB::table("role_sub_sections")
            ->where("role_id", $roleId)
            ->where("sub_section_id", $subSectionId)
            ->delete();

        DB::table("role_sub_sections")
            ->where("role_id", $roleId)
            ->where("sub_section_id", $existingRoleModuleId)
            ->update(["status" => "inactive"]);

        app(SystemCatalogSyncService::class)->sync();

        $this->assertDatabaseHas("companies_sub_sections", [
            "company_id" => 1,
            "sub_section_id" => $subSectionId,
            "status" => "inactive",
            "section_order" => 900,
            "sub_section_order" => 901,
        ]);
        $this->assertDatabaseHas("companies_sub_sections", [
            "company_id" => 1,
            "sub_section_id" => $existingRoleModuleId,
            "status" => "active",
        ]);

        $this->assertDatabaseHas("role_sub_sections", [
            "role_id" => $roleId,
            "sub_section_id" => $subSectionId,
            "status" => "active",
        ]);
        $this->assertDatabaseHas("role_sub_sections", [
            "role_id" => $roleId,
            "sub_section_id" => $existingRoleModuleId,
            "status" => "inactive",
        ]);

    }

    public function test_apply_layout_moves_the_catalog_without_reactivating_disabled_modules(): void {

        DB::table("sub_sections")->where("id", 11)->update(["dom_route" => "workspace.index"]);
        DB::table("sub_sections")->where("id", 10)->update(["dom_route" => "home.index"]);
        DB::table("sections")->insert([
            ["id" => 4, "slug" => "sc_customers", "name" => "customers"],
            ["id" => 12, "slug" => "sc_equipment", "name" => "equipment"],
        ]);
        DB::table("menu_groups")->where("id", 11)->update(["section_id" => 4]);
        DB::table("menu_groups")->where("id", 19)->update(["section_id" => 12]);
        DB::table("menu_groups")->insert([
            ["id" => 12, "section_id" => 4, "slug" => "memberships", "name" => "Membresías"],
            ["id" => 31, "section_id" => 2, "slug" => "customer_attendance", "name" => "Asistencia de clientes"],
        ]);
        DB::table("sub_sections")->whereIn("id", [40, 41])->update(["section_id" => 4]);
        DB::table("sub_sections")->where("id", 42)->update(["section_id" => 4, "menu_group_id" => 12]);
        DB::table("sub_sections")->where("id", 43)->update(["menu_group_id" => 31]);
        DB::table("sub_sections")->insert([
            "section_id" => 2,
            "menu_group_id" => 31,
            "slug" => "sc_local-attendance",
            "name" => "attendance-archive",
            "dom_route" => "local.attendance.archive",
            "status" => "inactive",
        ]);
        DB::table("sub_sections")->whereIn("id", [61, 62])->update(["section_id" => 12]);
        DB::table("companies_sub_sections")->where("sub_section_id", 40)->update([
            "status" => "inactive",
            "section_order" => 900,
            "sub_section_order" => 901,
        ]);
        DB::table("role_sub_sections")->where("sub_section_id", 61)->update(["status" => "inactive"]);
        $permissions = DB::table("role_sub_sections")->orderBy("id")->get()->toJson();

        $catalog = app(SystemCatalogSyncService::class);
        $summary = $catalog->applyLayout(SystemNavigationSeeder::layout());
        $this->assertSame($summary, $catalog->applyLayout(SystemNavigationSeeder::layout()));

        $this->assertDatabaseHas("sub_sections", ["id" => 11, "dom_route" => "home.index"]);
        $this->assertDatabaseHas("sub_sections", ["id" => 10, "dom_route" => "favorites.index"]);
        $this->assertDatabaseHas("sections", ["id" => 4, "status" => "inactive"]);
        $this->assertDatabaseHas("sections", ["id" => 12, "status" => "inactive"]);
        $this->assertSame(10, DB::table("sections")->where("status", "active")->count());
        $this->assertDatabaseHas("menu_groups", ["id" => 12, "status" => "inactive"]);
        $this->assertDatabaseHas("menu_groups", ["id" => 31, "status" => "active"]);
        $this->assertDatabaseHas("sub_sections", ["id" => 40, "section_id" => 2, "menu_group_id" => 11, "dom_route" => "customers.index"]);
        $this->assertDatabaseHas("sub_sections", ["id" => 42, "section_id" => 2, "menu_group_id" => 11, "order" => 2]);
        $this->assertDatabaseHas("sub_sections", ["id" => 43, "section_id" => 2, "menu_group_id" => 9, "order" => 2]);
        $this->assertDatabaseHas("sub_sections", ["slug" => "sc_local-attendance", "menu_group_id" => 31]);
        $this->assertDatabaseHas("sub_sections", ["id" => 61, "section_id" => 11, "menu_group_id" => 19, "dom_route" => "assets.index"]);
        $this->assertSame($permissions, DB::table("role_sub_sections")->orderBy("id")->get()->toJson());
        $this->assertDatabaseHas("companies_sub_sections", [
            "company_id" => 1,
            "sub_section_id" => 40,
            "status" => "inactive",
            "section_order" => 201,
            "sub_section_order" => 2001,
        ]);

    }

    public function test_merged_world_only_exposes_the_page_allowed_by_the_role(): void {

        $roleId = DB::table("roles")->insertGetId([
            "slug" => "customer-reader",
            "name" => "Lector de clientes",
            "is_full_access" => false,
            "status" => "active",
        ]);
        DB::table("role_sub_sections")->insert([
            "role_id" => $roleId,
            "sub_section_id" => 40,
            "actions" => json_encode(["view"]),
            "status" => "active",
        ]);

        $user = \App\Models\System\Organizations\User::query()->where("email", "admin@example.test")->firstOrFail();
        $user->update(["role_id" => $roleId]);
        $this->actingAs($user->fresh());

        app(SystemCatalogSyncService::class)->applyLayout(SystemNavigationSeeder::layout());

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ]);

        $this->get(route("customers.index"))
            ->assertOk()
            ->assertSee('<a href="'.route("customers.index").'" id="menu-parent-operations"', false)
            ->assertDontSee('data-navigation-panel="2"', false)
            ->assertDontSee('href="'.route("tracking_subscriptions.index").'"', false)
            ->assertDontSee('id="menu-parent-analytics"', false)
            ->assertDontSee("Rendimiento comercial")
            ->assertDontSee("Rotación y reposición");

        $this->getJson(route("tracking_subscriptions.index"))->assertForbidden();
        $this->get(route("home.index"))
            ->assertOk()
            ->assertSee('data-navigation-section-id=""', false)
            ->assertDontSee('id="menu-parent-workspace"', false);

    }

    public function test_apply_layout_leaves_local_pages_visible_in_worlds_marked_for_retirement(): void {

        DB::table("sections")->insert(["id" => 4, "slug" => "sc_customers", "name" => "customers"]);
        DB::table("sub_sections")->insert([
            "section_id" => 4,
            "slug" => "sc_local-customers",
            "name" => "local-customers",
            "dom_route" => "local_customers.index",
        ]);
        DB::table("menu_groups")->where("id", 11)->update(["section_id" => 4]);
        DB::table("sub_sections")->whereIn("id", [40, 41])->update(["section_id" => 4]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("La sección sc_customers conserva grupos o páginas.");

        try {
            app(SystemCatalogSyncService::class)->applyLayout(SystemNavigationSeeder::layout());
        } finally {
            $this->assertDatabaseHas("sections", ["id" => 4, "status" => "active"]);
            $this->assertDatabaseHas("sub_sections", ["section_id" => 4, "dom_route" => "local_customers.index"]);
            $this->assertDatabaseHas("menu_groups", ["id" => 11, "section_id" => 4]);
            $this->assertDatabaseHas("sub_sections", ["id" => 40, "section_id" => 4]);
        }

    }

    public function test_warehouse_inventory_projection_is_idempotent(): void {

        $currencyId = (int) DB::table("currencies")->value("id");
        $warehouseId = (int) DB::table("warehouses")->value("id");
        $timestamp = now();

        $products = collect(range(1, 3))
            ->map(fn(int $number): array => [
                "internal_code" => "BATCH-PRODUCT-{$number}",
                "name" => "Producto por lote {$number}",
                "price" => 10,
                "currency_id" => $currencyId,
                "type" => "product",
                "status" => "active",
                "created_at" => $timestamp,
            ])
            ->all();

        DB::table("items")->insert($products);

        WarehouseItemService::createForWarehouse($warehouseId, 1);

        $productIds = DB::table("items")
            ->where("internal_code", "like", "BATCH-PRODUCT-%")
            ->pluck("id");

        $this->assertSame(
            3,
            DB::table("warehouse_items")
                ->where("warehouse_id", $warehouseId)
                ->whereIn("item_id", $productIds)
                ->count()
        );

        $firstProductId = (int) $productIds->first();

        DB::table("warehouse_items")
            ->where("warehouse_id", $warehouseId)
            ->where("item_id", $firstProductId)
            ->update(["minimum_stock" => 7]);

        WarehouseItemService::createForWarehouse($warehouseId, 1);

        $this->assertSame(
            "7.000",
            DB::table("warehouse_items")
                ->where("warehouse_id", $warehouseId)
                ->where("item_id", $firstProductId)
                ->value("minimum_stock")
        );

        WarehouseItemService::syncProductInventory(
            $firstProductId,
            [[
                "warehouse_id" => $warehouseId,
                "minimum_stock" => 4,
            ]],
            1
        );

        $this->assertSame(
            "4.000",
            DB::table("warehouse_items")
                ->where("warehouse_id", $warehouseId)
                ->where("item_id", $firstProductId)
                ->value("minimum_stock")
        );

    }

    public function test_category_projection_is_deduplicated_and_idempotent(): void {

        $currencyId = (int) DB::table("currencies")->value("id");
        $timestamp = now();

        $itemId = (int) DB::table("items")->insertGetId([
            "internal_code" => "CATEGORY-BATCH-ITEM",
            "name" => "Ítem con categorías",
            "price" => 10,
            "currency_id" => $currencyId,
            "type" => "product",
            "status" => "active",
            "created_at" => $timestamp,
        ]);

        $firstCategoryId = (int) DB::table("categories")->insertGetId([
            "internal_code" => "CATEGORY-BATCH-1",
            "name" => "Categoría por lote 1",
            "status" => "active",
            "created_at" => $timestamp,
        ]);

        $secondCategoryId = (int) DB::table("categories")->insertGetId([
            "internal_code" => "CATEGORY-BATCH-2",
            "name" => "Categoría por lote 2",
            "status" => "active",
            "created_at" => $timestamp,
        ]);

        CategoryItemService::sync($itemId, [
            ["category_id" => $firstCategoryId],
            ["category_id" => $secondCategoryId],
            ["category_id" => $secondCategoryId],
            [],
        ], 1);

        $this->assertSame(
            2,
            DB::table("category_items")
                ->where("item_id", $itemId)
                ->where("status", "active")
                ->count()
        );

        CategoryItemService::sync(
            $itemId,
            [["category_id" => $secondCategoryId]],
            1
        );

        $this->assertDatabaseHas("category_items", [
            "item_id" => $itemId,
            "category_id" => $firstCategoryId,
            "status" => "inactive",
        ]);

        $this->assertDatabaseHas("category_items", [
            "item_id" => $itemId,
            "category_id" => $secondCategoryId,
            "status" => "active",
        ]);

        $this->assertSame(
            2,
            DB::table("category_items")
                ->where("item_id", $itemId)
                ->count()
        );

    }

    public function test_tenant_rejects_more_than_one_root_company(): void {

        $company = DB::table("companies")->where("id", 1)->first();

        DB::table("companies")->insert([
            "slug" => "unexpected-second-company",
            "internal_code" => "SECOND",
            "identity_document_type_id" => $company->identity_document_type_id,
            "document_number" => "99999999998",
            "legal_name" => "SEGUNDA EMPRESA NO VÁLIDA",
            "commercial_name" => "Segunda empresa",
            "currency_id" => $company->currency_id,
            "status" => "active",
            "created_at" => now(),
        ]);

        $context = app(TenantCompanyContext::class);
        $context->forget();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("exactamente una empresa raíz");

        $context->id();

    }

    public function test_full_access_role_cannot_bypass_a_company_disabled_module_by_url(): void {

        $user = \App\Models\System\Organizations\User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail();

        $disabledIds = DB::table("sub_sections")->whereIn("dom_route", ["dashboard.index", "purchases.create"])->pluck("id");
        DB::table("companies_sub_sections")->whereIn("sub_section_id", $disabledIds)->update(["status" => "inactive"]);

        $this->assertFalse(
            \App\Services\System\Organizations\Roles\RolePermissionService::canAccessRoute($user, "dashboard.index")
        );

        $this->assertFalse(
            \App\Services\System\Organizations\Roles\RolePermissionService::canAccessRoute($user, "purchases.create")
        );

        $this->assertTrue(
            \App\Services\System\Organizations\Roles\RolePermissionService::canAccessRoute($user, "products.index")
        );

    }

    public function test_reseeding_does_not_overwrite_existing_module_preferences(): void {

        $moduleId = DB::table("sub_sections")->where("dom_route", "master_data.index")->value("id");

        DB::table("sub_sections")->where("id", $moduleId)->update(["is_enabled_by_default" => false]);
        DB::table("companies_sub_sections")->where("sub_section_id", $moduleId)->update(["status" => "inactive"]);

        app(SystemNavigationSeeder::class)->run();

        $this->assertDatabaseHas("sub_sections", ["id" => $moduleId, "is_enabled_by_default" => false]);
        $this->assertDatabaseHas("companies_sub_sections", ["company_id" => 1, "sub_section_id" => $moduleId, "status" => "inactive"]);

    }

    public function test_disabling_a_module_revokes_role_permissions_and_cannot_fall_back_to_a_sibling_route(): void {

        $administrator = \App\Models\System\Organizations\User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail();

        $salesIndexId = (int) DB::table("sub_sections")
            ->where("dom_route", "sales.index")
            ->value("id");

        $salesCreateId = (int) DB::table("sub_sections")
            ->where("dom_route", "sales.create")
            ->value("id");

        DB::table("companies_sub_sections")->where("sub_section_id", $salesCreateId)->update(["status" => "inactive"]);
        $enabledIds = DB::table("companies_sub_sections")->where("status", "active")->pluck("sub_section_id")->all();
        \App\Services\System\Organizations\Companies\CompanySectionService::revokeDisabledRolePermissions($enabledIds);

        $this->assertTrue(
            \App\Services\System\Organizations\Roles\RolePermissionService::canAccessRoute(
                $administrator,
                "sales.index"
            )
        );

        $this->assertFalse(
            \App\Services\System\Organizations\Roles\RolePermissionService::canAccessRoute(
                $administrator,
                "sales.create"
            )
        );

        $this->assertDatabaseMissing("role_sub_sections", [
            "sub_section_id" => $salesCreateId,
        ]);

        Auth::login($administrator);

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("sales.create"))->assertForbidden();

    }

    public function test_customer_attendance_is_in_operations(): void {

        $attendance = DB::table("sub_sections")
            ->join("sections", "sections.id", "=", "sub_sections.section_id")
            ->join("menu_groups", "menu_groups.id", "=", "sub_sections.menu_group_id")
            ->where("sub_sections.dom_route", "tracking_attendances.index")
            ->first([
                "sections.dom_label as section_name",
                "menu_groups.name as group_name",
                "sub_sections.order",
            ]);

        $this->assertNotNull($attendance);
        $this->assertSame("Operaciones y clientes", $attendance->section_name);
        $this->assertSame("Servicios y asistencias", $attendance->group_name);
        $this->assertSame(2, (int) $attendance->order);

    }

    public function test_catalog_masters_have_classification_and_kitchen_groups(): void {

        $this->assertSame("Oferta comercial", DB::table("menu_groups")->where("id", 13)->value("name"));
        $this->assertDatabaseHas("sub_sections", ["id" => 52, "dom_label" => "Planes de membresía"]);
        $this->assertDatabaseHas("sub_sections", ["id" => 42, "dom_label" => "Membresías de clientes"]);
        $this->assertDatabaseMissing("menu_groups", ["id" => 14]);

        foreach([59 => [29, 1, 1], 50 => [13, 2, 1], 53 => [28, 3, 1], 55 => [28, 3, 2]] as $id => [$groupId, $groupOrder, $order]) {

            $this->assertDatabaseHas("sub_sections", [
                "id" => $id,
                "menu_group_id" => $groupId,
                "order" => $order,
            ]);

            $this->assertDatabaseHas("companies_sub_sections", [
                "sub_section_id" => $id,
                "sub_section_order" => \App\Support\System\NavigationOrder::item($groupOrder, $order),
            ]);

        }

    }

    public function test_catalog_page_exposes_its_world_and_group_for_the_breadcrumb(): void {

        $user = \App\Models\System\Organizations\User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail();

        Auth::login($user);

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("products.index"))
            ->assertOk()
            ->assertSee("data-navigation-world=\"Catálogo comercial\"", false)
            ->assertSee("data-navigation-group=\"Oferta comercial\"", false);

    }

    public function test_inventory_routes_have_a_separate_world_and_operational_groups(): void {

        $this->assertDatabaseHas("sections", ["slug" => "sc_inventory"]);

        $groups = DB::table("menu_groups")
            ->where("section_id", 5)
            ->orderBy("order")
            ->pluck("name")
            ->all();

        $this->assertSame(["Cocina", "Oferta comercial", "Clasificación"], $groups);
        $this->assertDatabaseMissing("menu_groups", ["id" => 16]);

        $masterRoutes = DB::table("sub_sections")
            ->where("menu_group_id", 13)
            ->orderBy("order")
            ->pluck("dom_route")
            ->all();

        $this->assertSame([
            "products.index",
            "services.index",
            "subscriptions.index",
        ], $masterRoutes);

        $routes = DB::table("sub_sections")
            ->join("sections", "sections.id", "=", "sub_sections.section_id")
            ->join("menu_groups", "menu_groups.id", "=", "sub_sections.menu_group_id")
            ->whereIn("sub_sections.dom_route", [
                "stocks_management.stock.index",
                "stocks_management.transfers.index",
                "stocks_management.guides.index",
                "stocks_management.kardex.index",
                "stocks_management.valued.index",
            ])
            ->orderBy("menu_groups.order")
            ->orderBy("sub_sections.order")
            ->get(["sections.dom_label as world", "menu_groups.name as group_name", "sub_sections.dom_route"]);

        $this->assertCount(5, $routes);
        $this->assertSame(["Inventario"], $routes->pluck("world")->unique()->values()->all());
        $this->assertSame(["Existencias", "Traslados y guías", "Movimientos y costos"], $routes->pluck("group_name")->unique()->values()->all());
        $this->assertSame([
            "stocks_management.stock.index",
            "stocks_management.transfers.index",
            "stocks_management.guides.index",
            "stocks_management.kardex.index",
            "stocks_management.valued.index",
        ], $routes->pluck("dom_route")->all());

        $orderedGroups = DB::table("companies_sub_sections as company_modules")
            ->join("sub_sections as modules", "modules.id", "=", "company_modules.sub_section_id")
            ->join("menu_groups as groups", "groups.id", "=", "modules.menu_group_id")
            ->where("company_modules.company_id", 1)
            ->where("modules.section_id", 6)
            ->orderBy("company_modules.sub_section_order")
            ->pluck("groups.name")
            ->unique()
            ->values()
            ->all();

        $this->assertSame(["Existencias", "Traslados y guías", "Movimientos y costos"], $orderedGroups);

    }

    public function test_tenant_navigation_renders_only_allowed_modules_and_marks_the_current_route(): void {

        DB::table("sub_sections")->where("dom_route", "brands.index")->update([
            "status" => "inactive",
            "is_enabled_by_default" => false,
        ]);

        $user = \App\Models\System\Organizations\User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail();

        Auth::login($user);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("dashboard.index"));

        $response->assertOk();
        $response->assertSee('class="fg-rail"', false);
        $response->assertSee('class="fg-navigation has-context"', false);
        $response->assertSee('id="menu-parent-workspace"', false);
        $response->assertSee('<span class="fg-user__avatar"', false);
        $response->assertSee('href="'.route("home.index").'"', false);
        $response->assertDontSee('data-navigation-panel="1"', false);
        $response->assertSee('data-navigation-panel="8"', false);
        $response->assertDontSee('data-search-label="Inicio"', false);
        $response->assertDontSee("br-navigation__brand", false);
        $response->assertDontSee("Volver a Inicio", false);
        $response->assertSee("id=\"brNavigationContext\"", false);
        $response->assertSee("data-navigation-panel=\"5\"", false);
        $response->assertSee('class="fg-context__page-icon fa-solid fa-chart-line" aria-hidden="true"', false);
        $response->assertSee('class="fg-context__page-icon fa-solid fa-utensils" aria-hidden="true"', false);
        $response->assertSee("id=\"menu-parent-items\"", false);
        $response->assertSee("id=\"menu-parent-sales\"", false);
        $response->assertSee("id=\"menu-parent-purchases\"", false);
        $response->assertSee("id=\"menu-parent-cash\"", false);
        $response->assertDontSee("id=\"brNavigationPreview\"", false);
        $response->assertSee('data-navigation-world="Analítica"', false);
        $response->assertDontSee("br-menu-category__full", false);
        $response->assertDontSee('id="menu-items-brands"', false);
        $response->assertSee('class="fa-solid fa-gear" aria-hidden="true"', false);
        $response->assertSee('href="'.route("analytics.commercial_performance.index").'"', false);
        $response->assertSee('href="'.route("analytics.stock_turnover_replenishment.index").'"', false);
        $response->assertSee('data-search-label="Rendimiento comercial"', false);
        $response->assertSee('data-search-label="Rotación y reposición"', false);

        $document = new \DOMDocument("1.0", "UTF-8");
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $rows = $xpath->query('//*[@data-navigation-panel="8"]//li');
        $this->assertSame([
            "Resumen del negocio",
            "Rendimiento comercial",
            "Rotación y reposición",
            "Reportes",
        ], array_map(fn($row) => preg_replace('/\s+/u', ' ', trim($row->textContent)), iterator_to_array($rows)));
        $this->assertSame(4, $xpath->query('//*[@data-navigation-panel="8"]//a')->length);

    }

    public function test_analytics_links_directly_when_only_one_page_is_enabled(): void {

        DB::table("companies_sub_sections")->whereIn("sub_section_id", [80, 110, 111])->update(["status" => "inactive"]);
        $user = \App\Models\System\Organizations\User::query()->where("email", "admin@example.test")->firstOrFail();
        $this->actingAs($user);

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("dashboard.index"))
            ->assertOk()
            ->assertDontSee('data-navigation-panel="8"', false)
            ->assertSee('<a href="'.route("dashboard.index").'" id="menu-parent-analytics"', false)
            ->assertDontSee("Rotación y reposición")
            ->assertDontSee("Rendimiento comercial")
            ->assertDontSee('href="'.route("reports.index").'"', false);

    }

    public function test_navigation_seeder_inserts_the_workspace_icon_once(): void {

        $this->assertSame("fa-solid fa-house", DB::table("sections")
            ->where("slug", "sc_workspace")
            ->value("dom_icon"));

        DB::table("sections")->where("slug", "sc_workspace")->update(["dom_icon" => "fa-solid fa-compass"]);

        app(SystemNavigationSeeder::class)->run();

        $this->assertSame("fa-solid fa-compass", DB::table("sections")
            ->where("slug", "sc_workspace")
            ->value("dom_icon"));

    }

    public function test_start_world_is_direct_and_analytics_has_its_own_group(): void {

        $this->assertDatabaseHas("sections", ["id" => 1, "has_sub_menu" => false]);
        $this->assertSame(["home.index"], DB::table("sub_sections")->where("section_id", 1)->pluck("dom_route")->all());
        $this->assertDatabaseHas("sub_sections", ["id" => 11, "section_id" => 1, "menu_group_id" => null]);
        $this->assertSame(
            [
                "dashboard.index" => "Resumen del negocio",
                "analytics.commercial_performance.index" => "Rendimiento comercial",
                "analytics.stock_turnover_replenishment.index" => "Rotación y reposición",
                "reports.index" => "Reportes",
            ],
            DB::table("sub_sections")->where("section_id", 8)->where("status", "active")
                ->orderBy("order")->pluck("dom_label", "dom_route")->all()
        );

    }

    public function test_workspace_pages_keep_their_own_panel_when_the_logo_links_home(): void {

        DB::table("sub_sections")->where("dom_route", "assets.index")->update([
            "section_id" => 1,
            "menu_group_id" => null,
        ]);
        $this->actingAs(\App\Models\System\Organizations\User::query()->where("email", "admin@example.test")->firstOrFail());

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("home.index"));

        $response->assertOk()
            ->assertSee('id="menu-parent-workspace"', false)
            ->assertSee('data-navigation-panel="1"', false);

        $document = new \DOMDocument("1.0", "UTF-8");
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//header[@id="layout-navbar"]//a[@class="fg-topbar__brand" and @href="'.route("home.index").'" and @aria-current="page"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@id="brNavigationWorlds"]//button[@id="menu-parent-workspace" and @aria-label="Espacio de trabajo"]')->length);
        $links = $xpath->query('//*[@data-navigation-panel="1"]//a');
        $this->assertSame([route("assets.index")], array_map(fn($link) => $link->getAttribute("href"), iterator_to_array($links)));
        $this->assertSame("false", $links->item(0)->getAttribute("aria-current"));

    }

    public function test_layout_releases_stock_turnover_without_overwriting_company_choices(): void {
        $this->assertTrue(Route::has("analytics.commercial_performance.index"));
        $this->assertTrue(Route::has("analytics.stock_turnover_replenishment.index"));
        $this->assertDatabaseHas("sub_sections", ["id" => 110, "status" => "active", "is_enabled_by_default" => true]);
        $this->assertDatabaseHas("sub_sections", ["id" => 111, "status" => "active", "is_enabled_by_default" => true]);

        $this->assertSame(
            ["Resumen del negocio", "Rendimiento comercial", "Rotación y reposición", "Reportes"],
            DB::table("sub_sections")->where("section_id", 8)->where("menu_group_id", 24)
                ->orderBy("order")->pluck("dom_label")->all()
        );
        foreach(["companies_sub_sections", "role_sub_sections"] as $table) {
            $this->assertDatabaseHas($table, ["sub_section_id" => 110, "status" => "active"]);
            $this->assertDatabaseHas($table, ["sub_section_id" => 111, "status" => "active"]);
            DB::table($table)->where("sub_section_id", 111)->delete();
        }

        DB::table("sub_sections")->where("id", 111)->update(["status" => "inactive", "is_enabled_by_default" => false]);
        $catalog = app(SystemCatalogSyncService::class);
        $catalog->applyLayout(SystemNavigationSeeder::layout());

        $this->assertDatabaseHas("sub_sections", ["id" => 111, "status" => "active", "is_enabled_by_default" => true]);
        $this->assertDatabaseHas("companies_sub_sections", ["sub_section_id" => 111, "status" => "active"]);
        $this->assertDatabaseHas("role_sub_sections", ["sub_section_id" => 111, "status" => "active"]);

        DB::table("companies_sub_sections")->where("sub_section_id", 111)->update(["status" => "inactive"]);
        $catalog->applyLayout(SystemNavigationSeeder::layout());
        $this->assertDatabaseHas("companies_sub_sections", ["sub_section_id" => 111, "status" => "inactive"]);
    }


    public function test_cash_pages_use_independent_routes_without_legacy_page_paths(): void {

        $expectedPaths = [
            "cash_registers.index" => "/cash_registers",
            "cash_sessions.index" => "/cash-sessions",
            "cash_movements.index" => "/cash-movements",
            "cash_summary.index" => "/cash-summary",
        ];

        foreach($expectedPaths as $routeName => $path) {

            $this->assertTrue(Route::has($routeName));
            $this->assertSame($path, parse_url(route($routeName), PHP_URL_PATH));

        }

        $this->assertFalse(Route::has("cash_registers.registers.index"));

        $this->assertFalse(Route::has("cash_registers.sessions.index"));
        $this->assertFalse(Route::has("cash_registers.movements.index"));
        $this->assertFalse(Route::has("cash_registers.summary.index"));
        $this->assertTrue(Route::has("cash_sessions.list"));
        $this->assertTrue(Route::has("cash_sessions.open"));
        $this->assertTrue(Route::has("cash_sessions.close"));
        $this->assertTrue(Route::has("cash_movements.list"));
        $this->assertTrue(Route::has("cash_movements.store"));
        $this->assertTrue(Route::has("cash_movements.export"));
        $this->assertTrue(Route::has("cash_summary.data"));
        $this->assertFalse(Route::has("cash_registers.sessions"));
        $this->assertFalse(Route::has("cash_registers.movements"));
        $this->assertFalse(Route::has("cash_registers.summary"));

    }

    public function test_misc_expenses_initialization_is_available(): void {

        $user = \App\Models\System\Organizations\User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail();

        Auth::login($user);
        $withoutTenantMiddleware = [
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ];

        $this->withoutMiddleware($withoutTenantMiddleware)
            ->get(route("misc_expenses.initParams"))
            ->assertOk()
            ->assertJsonPath("bool", true)
            ->assertJsonCount(5, "data.categories")
            ->assertJsonCount(1, "data.currencies");

    }

    public function test_customer_communications_stay_with_customers(): void {

        $customerSections = DB::table("sub_sections")
            ->join("sections", "sections.id", "=", "sub_sections.section_id")
            ->whereIn("sub_sections.dom_route", [
                "tracking_notifications.index",
                "book_complaints.index",
            ])
            ->pluck("sections.dom_label")
            ->unique()
            ->values()
            ->all();

        $this->assertSame(["Operaciones y clientes"], $customerSections);
        $this->assertFalse(DB::table("sections")->where("dom_label", "Atención al cliente")->exists());
        $this->assertFalse(DB::table("sections")->where("dom_label", "Colaboradores")->exists());

    }

    public function test_staff_equipment_and_configuration_have_their_own_modules(): void {

        $this->assertDatabaseMissing("sections", ["slug" => "sc_infrastructure"]);
        $this->assertSame(1, DB::table("sections")->where("menu_category_id", 4)->count());
        $this->assertDatabaseMissing("menu_groups", ["id" => 17]);
        $this->assertDatabaseMissing("menu_groups", ["id" => 18]);

        $groups = DB::table("menu_groups")
            ->where("section_id", 7)
            ->orderBy("order")
            ->pluck("name")
            ->all();

        $this->assertSame(["Preferencias", "Empresa y sedes", "Seguridad y acceso", "Configuración del negocio"], $groups);
        $this->assertDatabaseMissing("menu_groups", ["id" => 22]);
        $this->assertSame(
            ["Personal", "Activos", "Control de acceso"],
            DB::table("menu_groups")->where("section_id", 11)->orderBy("order")->pluck("name")->all()
        );

        $routesByWorld = [
            7 => [
                "account.index",
                "favorites.index",
                "companies.index",
                "branches.index",
                "roles.index",
                "master_data.index",
            ],
            11 => ["users.index", "user_attendances.index", "assets.index", "assets_management.index", "biometric_devices.index"],
        ];

        foreach($routesByWorld as $sectionId => $expectedRoutes) {
            $this->assertSame($expectedRoutes, DB::table("companies_sub_sections as company_modules")
                ->join("sub_sections as modules", "modules.id", "=", "company_modules.sub_section_id")
                ->where("company_modules.company_id", 1)
                ->where("modules.section_id", $sectionId)
                ->orderBy("company_modules.sub_section_order")
                ->pluck("modules.dom_route")
                ->all());
        }

    }

    public function test_configuration_access_is_inserted_in_its_final_group(): void {

        $this->assertSame("Configuración del negocio", DB::table("menu_groups")->where("id", 21)->value("name"));
        $this->assertDatabaseMissing("menu_groups", ["id" => 22]);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable("business_industries"));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable("business_industry_module_sets"));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn("companies", "business_industry_id"));
        $this->assertFalse(Route::has("business_profile.index"));
        $this->assertSame([73], DB::table("sub_sections")->where("menu_group_id", 21)->pluck("id")->all());

        $this->assertDatabaseHas("companies_sub_sections", [
            "sub_section_id" => 73,
            "sub_section_order" => \App\Support\System\NavigationOrder::item(4, 1),
        ]);

        $this->assertDatabaseHas("sub_sections", [
            "id" => 10,
            "section_id" => 7,
            "menu_group_id" => 23,
            "dom_route" => "favorites.index",
            "is_enabled_by_default" => true,
        ]);
        $this->assertDatabaseHas("companies_sub_sections", [
            "company_id" => 1,
            "sub_section_id" => 10,
            "status" => "active",
        ]);

    }

    public function test_account_page_uses_configuration_and_preferences_navigation(): void {

        Auth::login(\App\Models\System\Organizations\User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail());

        $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ])->get(route("account.index"))
            ->assertOk()
            ->assertSee('id="app" data-endpoint="'.route("account.data").'"', false)
            ->assertSee('data-navigation-world="Configuración"', false)
            ->assertSee('data-navigation-group="Preferencias"', false);

        $this->getJson(route("account.index"))
            ->assertOk()
            ->assertSee('id="app" data-endpoint="'.route("account.data").'"', false);

        $this->getJson(route("account.data"))
            ->assertOk()
            ->assertJsonStructure(["account" => ["email"], "company" => ["name"]]);

    }

    public function test_purchase_receipts_and_payables_have_independent_routes_and_navigation(): void {

        $this->assertTrue(Route::has("purchases.receipts.index"));
        $this->assertTrue(Route::has("accounts_payable.index"));
        $this->assertTrue(Route::has("accounts_payable.list"));
        $this->assertSame("/purchases/pending-receipts", parse_url(route("purchases.receipts.index"), PHP_URL_PATH));
        $this->assertSame("/accounts_payable", parse_url(route("accounts_payable.index"), PHP_URL_PATH));

        $items = DB::table("sub_sections")
            ->whereIn("sub_sections.dom_route", ["purchases.receipts.index", "accounts_payable.index"])
            ->orderBy("sub_sections.order")
            ->get(["sub_sections.menu_group_id", "sub_sections.dom_route"]);

        $this->assertCount(2, $items);
        $this->assertSame([25], $items->pluck("menu_group_id")->unique()->values()->all());
        $this->assertSame(["purchases.receipts.index", "accounts_payable.index"], $items->pluck("dom_route")->all());

        $user = \App\Models\System\Organizations\User::query()
            ->where("email", "admin@example.test")
            ->firstOrFail();

        Auth::login($user);
        $middleware = [
            \App\Http\Middleware\ResolveTenant::class,
            \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\EnsureTenantSession::class,
            \App\Http\Middleware\EnsureAuthenticatedSession::class,
            \App\Http\Middleware\EnsureModulePermission::class,
            \App\Http\Middleware\EnsureOperationalScope::class,
        ];

        $this->withoutMiddleware($middleware)
            ->get(route("purchases.receipts.index"))
            ->assertOk();

        $this->withoutMiddleware($middleware)
            ->get(route("accounts_payable.list"))
            ->assertOk()
            ->assertJsonPath("bool", true)
            ->assertJsonPath("summary.accounts", 0);

    }

}
