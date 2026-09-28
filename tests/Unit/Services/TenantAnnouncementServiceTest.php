<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\System\Tenancy\{TenantAnnouncement, TenantDatabase};
use App\Services\System\Tenancy\{TenantAnnouncementService};
use Illuminate\Support\Facades\{Cache};
use Tests\{TestCase};

final class TenantAnnouncementServiceTest extends TestCase {
    public function test_visibility_checks_dates_after_reading_a_tenant_scoped_cache(): void {

        $tenant = new TenantDatabase([
            "public_id" => "018fd259-23a8-74c0-b63d-05b0e856cf3d",
        ]);

        $announcements = collect([
            new TenantAnnouncement(["title" => "Visible"]),
            new TenantAnnouncement([
                "title" => "Programado",
                "starts_at" => now()->addMinute(),
            ]),
            new TenantAnnouncement([
                "title" => "Vencido",
                "ends_at" => now()->subMinute(),
            ]),
        ]);

        Cache::put(TenantAnnouncementService::cacheKey($tenant), $announcements, 30);

        $service = app(TenantAnnouncementService::class);

        $this->assertSame(
            ["Visible"],
            $service->visibleFor($tenant)->pluck("title")->all()
        );

        $service->forgetFor($tenant);

        $this->assertFalse(Cache::has(TenantAnnouncementService::cacheKey($tenant)));

    }
}
