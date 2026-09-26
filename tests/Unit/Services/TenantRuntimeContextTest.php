<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\System\Tenancy\{TenantStatus};
use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Tenancy\{TenantContext, TenantStoragePath};
use Tests\{TestCase};

final class TenantRuntimeContextTest extends TestCase {
    public function test_storage_namespace_uses_public_tenant_id_instead_of_slug_or_company(): void {

        $tenant = new TenantDatabase([
            "public_id" => "018fd259-23a8-74c0-b63d-05b0e856cf3d",
            "slug" => "cliente-visible",
            "database_name" => "blapos_tenant_cliente",
        ]);

        app(TenantContext::class)->set($tenant);

        $this->assertSame(
            "tenants/018fd259-23a8-74c0-b63d-05b0e856cf3d/sales/2026/venta.pdf",
            TenantStoragePath::sales("2026/venta.pdf")
        );

        $this->assertStringNotContainsString("cliente-visible", TenantStoragePath::branding("logo.svg"));

    }

    public function test_only_active_status_accepts_tenant_traffic(): void {

        $this->assertTrue(TenantStatus::ACTIVE->acceptsTraffic());

        foreach(TenantStatus::cases() as $status) {

            if($status !== TenantStatus::ACTIVE) {

                $this->assertFalse($status->acceptsTraffic());

            }

        }

    }
}
