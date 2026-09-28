<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\System\Tenancy\{TenantAnnouncementService, TenantContext};
use Closure;
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{Schema};
use Symfony\Component\HttpFoundation\{Response};

final class ShareTenantAnnouncements {
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantAnnouncementService $announcements
    ) {
    }

    public function handle(Request $request, Closure $next): Response {

        $tenant = $this->context->get();
        $announcements = collect();

        if($tenant && Schema::connection("landlord")->hasTable("tenant_announcements")) {

            $announcements = $this->announcements->visibleFor($tenant);

        }

        view()->share("tenantAnnouncements", $announcements);

        return $next($request);

    }
}
