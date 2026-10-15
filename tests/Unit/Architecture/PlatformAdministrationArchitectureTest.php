<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\{TestCase};

final class PlatformAdministrationArchitectureTest extends TestCase {

    public function test_authenticated_platform_uses_one_asynchronous_shell(): void {

        $routes = file_get_contents(base_path("routes/platform.php"));
        $layout = file_get_contents(resource_path("views/Platform/layouts/app.blade.php"));
        $shell = file_get_contents(resource_path("views/Platform/shell.blade.php"));

        $this->assertStringContainsString("PlatformShellController", $routes);
        $this->assertStringContainsString("/api/tenants", $routes);
        $this->assertStringContainsString("@stack(\"scripts\")", str_replace("'", "\"", $layout));
        $this->assertStringContainsString("resources/js/Platform/app.js", $shell);
        $this->assertFileDoesNotExist(resource_path("views/Platform/tenants/index.blade.php"));
        $this->assertFileDoesNotExist(resource_path("views/Platform/tenants/show.blade.php"));

    }

    public function test_platform_uses_opaque_tenant_routes_and_protected_profile_updates(): void {

        $migration = file_get_contents(database_path("migrations/landlord/2026_06_25_000001_create_tenant_registry_tables.php"));
        $tenantModel = file_get_contents(app_path("Models/System/Tenancy/TenantDatabase.php"));
        $profileController = file_get_contents(app_path("Http/Controllers/Platform/PlatformProfileController.php"));

        $this->assertStringContainsString("\$table->uuid(\"public_id\")->unique()", $migration);
        $this->assertStringContainsString("return \"public_id\"", $tenantModel);
        $this->assertStringContainsString("Hash::check(\$data[\"current_password\"]", $profileController);
        $this->assertStringContainsString("\"session_version\"", $profileController);
        $this->assertStringNotContainsString("/tenants/(\\d+)", file_get_contents(resource_path("js/Platform/App.vue")));

    }

    public function test_module_configuration_replaces_the_company_projection_atomically(): void {

        $service = file_get_contents(app_path("Services/System/Tenancy/PlatformTenantService.php"));
        $migration = file_get_contents(database_path("migrations/2024_01_11_223124_create_init_masters_table.php"));

        $this->assertStringContainsString("DB::transaction(", $service);
        $this->assertStringContainsString("->delete();", $service);
        $this->assertStringContainsString("->insert(\$records);", $service);
        $this->assertStringContainsString("revokeDisabledRolePermissions", $service);
        $this->assertStringNotContainsString("->updateOrInsert(", $service);
        $this->assertStringContainsString("companies_sub_sections_company_module_unique", $migration);
        $this->assertStringContainsString("sub_sections_dom_route_unique", $migration);

    }

    public function test_role_scope_schema_is_defined_in_the_base_migrations(): void {

        $masterMigration = file_get_contents(database_path("migrations/2024_01_11_223124_create_init_masters_table.php"));
        $companyMigration = file_get_contents(database_path("migrations/2024_02_11_223124_create_init_companies_table.php"));
        $salesMigration = file_get_contents(database_path("migrations/2024_03_11_053936_create_init_sales_table.php"));

        $this->assertStringContainsString("\$table->json(\"actions\")", $masterMigration);
        $this->assertStringContainsString("Schema::create(\"role_branches\"", $companyMigration);
        $this->assertStringContainsString("Schema::create(\"user_warehouses\"", $companyMigration);
        $this->assertStringContainsString("Schema::create(\"sale_delivery_methods\"", $salesMigration);
        $this->assertFileDoesNotExist(database_path("migrations/2026_07_01_000001_expand_role_permissions_and_operational_scopes.php"));
        $this->assertFileDoesNotExist(database_path("migrations/2026_08_07_000002_separate_sale_delivery_method_and_status.php"));

    }

    public function test_landlord_schema_has_a_single_migration_source(): void {

        $schemaService = file_get_contents(app_path("Services/System/Tenancy/LandlordSchemaService.php"));

        $this->assertStringNotContainsString("->create(", $schemaService);
        $this->assertStringContainsString("platform:install", $schemaService);

    }

    public function test_platform_provisioning_is_locked_and_uses_a_blocking_modal(): void {

        $provisioner = file_get_contents(app_path("Services/System/Tenancy/PlatformTenantProvisioner.php"));
        $tenantIndex = file_get_contents(resource_path("js/Platform/pages/TenantIndex.vue"));
        $tenantDetail = file_get_contents(resource_path("js/Platform/pages/TenantDetail.vue"));

        $this->assertStringContainsString("Cache::lock(", $provisioner);
        $this->assertStringContainsString("platform-provisioning-lock", $tenantIndex);
        $this->assertStringContainsString("beforeunload", $tenantIndex);
        $this->assertStringContainsString("platform-check__box", $tenantDetail);

    }

}
