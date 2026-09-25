<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Models\System\General\{Section};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use App\Services\System\Tenancy\{TenantContext};
use Illuminate\Database\Eloquent\{Collection};
use Illuminate\Support\Facades\{Cache, DB, Route};
use InvalidArgumentException;

/**
 * Resolves and caches the modules enabled for a company.
 */
final class CompanySectionService {
    private const CACHE_TTL = 1800;

    private const CACHE_PREFIX = "company_sections";

    public static function getSections(int $companyId, ?int $roleId = null, bool $forceRefresh = false): Collection {

        self::validateCompanyId($companyId);

        $cacheKey = self::cacheKey($companyId, $roleId);

        if($forceRefresh) {

            Cache::forget($cacheKey);

        }

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => self::querySections($companyId, $roleId)
        );

    }

    public static function clearCache(int $companyId, ?int $roleId = null): void {

        self::validateCompanyId($companyId);

        Cache::forget(self::cacheKey($companyId, $roleId));

    }

    public static function clearCompanyCache(int $companyId): void {

        self::clearCache($companyId);
        RolePermissionService::clearCompanyCache($companyId);

        \App\Models\System\Organizations\Role::query()
            ->pluck("id")
            ->each(fn($roleId) => self::clearCache($companyId, (int) $roleId));

    }

    public static function revokeDisabledRolePermissions(int $companyId, array $enabledSubSectionIds): int {

        self::validateCompanyId($companyId);

        $enabledIds = collect($enabledSubSectionIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $permissions = DB::table("role_sub_sections");

        if($enabledIds->isNotEmpty()) {

            $permissions->whereNotIn("sub_section_id", $enabledIds->all());

        }

        return $permissions->delete();

    }

    public static function cacheKey(int $companyId, ?int $roleId = null): string {

        self::validateCompanyId($companyId);

        return app(TenantContext::class)->cacheNamespace().":".self::CACHE_PREFIX.":role:".($roleId ?: "all");

    }

    private static function querySections(int $companyId, ?int $roleId = null): Collection {

        $mustFilterByRole = $roleId && !RolePermissionService::isFullAccess($companyId, $roleId);
        $allowedSubSectionIds = $mustFilterByRole
            ? RolePermissionService::allowedSubSectionIds($companyId, $roleId)
            : [];

        $query = Section::query()
            ->select([
                "id",
                "menu_category_id",
                "slug",
                "name",
                "order",
                "dom_id",
                "dom_label",
                "dom_icon",
                "has_sub_menu",
                "status",
            ])
            ->selectSub(function($subQuery) use ($companyId) {

                $subQuery->from("companies_sub_sections")
                    ->join("sub_sections", "sub_sections.id", "=", "companies_sub_sections.sub_section_id")
                    ->whereColumn("sub_sections.section_id", "sections.id")
                    ->where("companies_sub_sections.status", "active")
                    ->selectRaw("MIN(companies_sub_sections.section_order)");

            }, "company_section_order")
            ->where("status", "active")
            ->whereHas("subSections.companiesSubSections", function($query) use ($companyId) {

                $query->where("company_id", $companyId)
                    ->where("status", "active");

            });

        if($mustFilterByRole) {

            $query->whereHas("subSections", function($subQuery) use ($allowedSubSectionIds) {

                $subQuery->whereIn("id", $allowedSubSectionIds);

            });

        }

        return $query->with(["menuCategory:id,slug,name,order,status", "subSections" => function($query) use ($companyId, $allowedSubSectionIds, $mustFilterByRole) {

            $query->select([
                "id",
                "section_id",
                "menu_group_id",
                "slug",
                "name",
                "description",
                "order",
                "dom_id",
                "dom_label",
                "dom_icon",
                "dom_route",
                "status",
            ])
                ->selectSub(function($subQuery) use ($companyId) {

                    $subQuery->from("companies_sub_sections")
                        ->whereColumn("companies_sub_sections.sub_section_id", "sub_sections.id")
                        ->where("companies_sub_sections.status", "active")
                        ->selectRaw("MIN(companies_sub_sections.sub_section_order)");

                }, "company_sub_section_order")
                ->whereHas("companiesSubSections", function($companyQuery) use ($companyId) {

                    $companyQuery->where("company_id", $companyId)
                        ->where("status", "active");

                });

            if($mustFilterByRole) {

                $query->whereIn("id", $allowedSubSectionIds);

            }

            $query->orderByRaw("COALESCE(company_sub_section_order, `sub_sections`.`order`, 999)")
                ->orderBy("sub_sections.order");

        }, "subSections.menuGroup:id,section_id,slug,name,order,status"])
            ->orderByRaw("COALESCE(company_section_order, `sections`.`order`, 999)")
            ->orderBy("sections.order")
            ->get()
            ->each(function(Section $section) {

                $section->setAppends([]);

                $section->subSections->each(function($subSection) {

                    $subSection->setAppends([]);
                    $subSection->dom_route_url = Route::has($subSection->dom_route)
                        ? route($subSection->dom_route)
                        : "#";

                });

            });

    }

    private static function validateCompanyId(int $companyId): void {

        if($companyId <= 0) {

            throw new InvalidArgumentException("Company ID must be greater than zero.");

        }

    }
}
