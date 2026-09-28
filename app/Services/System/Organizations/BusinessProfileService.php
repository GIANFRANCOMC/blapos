<?php

declare(strict_types=1);

namespace App\Services\System\Organizations;

use App\Models\System\General\{SubSection};
use App\Models\System\Organizations\{BusinessIndustry, BusinessIndustryModuleSet};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Collection};

final class BusinessProfileService {
    private const PROTECTED_ROUTES = [
        "workspace.index",
        "home.index",
        "account.index",
        "business_profile.index",
    ];

    public static function industries() {

        return BusinessIndustry::query()
            ->where("status", "active")
            ->with("moduleSets.subSection:id,dom_label,dom_route")
            ->orderBy("name")
            ->get();

    }

    public static function applyIndustry(int $industryId, int $userId): void {

        $companyId = app(TenantCompanyContext::class)->id();
        DB::transaction(function() use ($companyId, $industryId, $userId) {

            $industry = BusinessIndustry::query()
                ->whereKey($industryId)
                ->firstOrFail();

            $sets = BusinessIndustryModuleSet::query()
                ->where("business_industry_id", $industry->id)
                ->where("status", "active")
                ->get();

            $catalogIds = SubSection::query()
                ->where("status", "active")
                ->pluck("id")
                ->map(fn($id) => (int) $id);

            $protectedIds = self::protectedModuleIds();
            $selectedIds = $sets
                ->where("is_enabled_by_default", true)
                ->pluck("sub_section_id")
                ->map(fn($id) => (int) $id)
                ->merge($protectedIds)
                ->intersect($catalogIds)
                ->unique();

            self::replaceEnabledModules($catalogIds, $selectedIds, $userId);

            DB::table("companies")
                ->where("id", $companyId)
                ->update([
                    "business_industry_id" => $industry->id,
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ]);

        });

    }

    public static function enabledModuleIds(): array {

        $companyId = app(TenantCompanyContext::class)->id();

        return DB::table("companies_sub_sections")
            ->where("company_id", $companyId)
            ->where("status", "active")
            ->pluck("sub_section_id")
            ->map(fn($id) => (int) $id)
            ->all();

    }

    public static function updateModules(array $enabledIds, int $userId): void {

        DB::transaction(function() use ($enabledIds, $userId) {

            $catalogIds = SubSection::query()
                ->where("status", "active")
                ->pluck("id")
                ->map(fn($id) => (int) $id);

            $protectedIds = self::protectedModuleIds();
            $selected = collect($enabledIds)
                ->map(fn($id) => (int) $id)
                ->intersect($catalogIds)
                ->merge($protectedIds)
                ->unique();

            self::replaceEnabledModules($catalogIds, $selected, $userId);

        });

    }

    private static function protectedModuleIds() {

        return SubSection::query()
            ->whereIn("dom_route", self::PROTECTED_ROUTES)
            ->pluck("id")
            ->map(fn($id) => (int) $id);

    }

    private static function replaceEnabledModules(
        Collection $catalogIds,
        Collection $selectedIds,
        int $userId
    ): void {

        $companyId = app(TenantCompanyContext::class)->id();
        $timestamp = now();

        DB::table("companies_sub_sections")
            ->where("company_id", $companyId)
            ->whereIn("sub_section_id", $catalogIds->all())
            ->update([
                "status" => "inactive",
                "updated_at" => $timestamp,
                "updated_by" => $userId,
            ]);

        $records = $selectedIds
            ->map(fn($subSectionId): array => [
                "company_id" => $companyId,
                "sub_section_id" => (int) $subSectionId,
                "status" => "active",
                "updated_at" => $timestamp,
                "updated_by" => $userId,
            ])
            ->values()
            ->all();

        if($records !== []) {

            DB::table("companies_sub_sections")->upsert(
                $records,
                ["company_id", "sub_section_id"],
                ["status", "updated_at", "updated_by"]
            );

        }

        CompanySectionService::revokeDisabledRolePermissions($selectedIds->values()->all());
        CompanySectionService::clearTenantCache();

    }
}
