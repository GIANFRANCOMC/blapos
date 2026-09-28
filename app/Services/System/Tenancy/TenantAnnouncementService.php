<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Models\System\Tenancy\{TenantAnnouncement, TenantDatabase};
use Illuminate\Support\Facades\{Cache};
use Illuminate\Support\{Collection};

final class TenantAnnouncementService {
    public function visibleFor(TenantDatabase $tenant): Collection {

        $announcements = Cache::remember(
            self::cacheKey($tenant),
            now()->addSeconds(30),
            fn() => TenantAnnouncement::query()
                ->where("status", "active")
                ->where(fn($query) => $query
                    ->whereNull("tenant_database_id")
                    ->orWhere("tenant_database_id", $tenant->id))
                ->orderByDesc("created_at")
                ->get()
        );

        $now = now();

        return $announcements
            ->filter(fn(TenantAnnouncement $announcement): bool => (
                ($announcement->starts_at === null || $announcement->starts_at <= $now)
                && ($announcement->ends_at === null || $announcement->ends_at >= $now)
            ))
            ->values();

    }

    public function forgetFor(TenantDatabase $tenant): void {

        Cache::forget(self::cacheKey($tenant));

    }

    public static function cacheKey(TenantDatabase $tenant): string {

        return "tenant:".$tenant->public_id.":announcements";

    }
}
