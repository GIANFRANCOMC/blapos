<?php

declare(strict_types=1);

namespace App\Services\System\Organizations;

use App\Models\System\General\{SubSection};
use App\Models\System\Organizations\{BusinessIndustry, BusinessIndustryModuleSet};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use Illuminate\Support\Facades\{DB};

final class BusinessProfileService {
    private const PROTECTED_ROUTES = [
        "workspace.index",
        "home.index",
        "account.index",
        "business_profile.index",
    ];

    public static function industries(int $companyId) {

        return BusinessIndustry::query()
            ->where("status", "active")
            ->with("moduleSets.subSection:id,dom_label,dom_route")
            ->orderBy("name")
            ->get();

    }

    public static function applyIndustry(int $companyId, int $industryId, int $userId): void {

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

            DB::table("companies_sub_sections")
                ->where("company_id", $companyId)
                ->whereIn("sub_section_id", $catalogIds->all())
                ->update([
                    "status" => "inactive",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ]);

            foreach($selectedIds as $subSectionId) {

                DB::table("companies_sub_sections")->updateOrInsert(
                    ["company_id" => $companyId, "sub_section_id" => $subSectionId],
                    [
                        "status" => "active",
                        "updated_at" => now(),
                        "updated_by" => $userId,
                    ]
                );

            }

            CompanySectionService::revokeDisabledRolePermissions($companyId, $selectedIds->values()->all());

            DB::table("companies")
                ->where("id", $companyId)
                ->update([
                    "business_industry_id" => $industry->id,
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ]);

            CompanySectionService::clearCompanyCache($companyId);

        });

    }

    public static function enabledModuleIds(int $companyId): array {

        return DB::table("companies_sub_sections")
            ->where("company_id", $companyId)
            ->where("status", "active")
            ->pluck("sub_section_id")
            ->map(fn($id) => (int) $id)
            ->all();

    }

    public static function updateModules(int $companyId, array $enabledIds, int $userId): void {

        DB::transaction(function() use ($companyId, $enabledIds, $userId) {

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

            DB::table("companies_sub_sections")
                ->where("company_id", $companyId)
                ->whereIn("sub_section_id", $catalogIds->all())
                ->update([
                    "status" => "inactive",
                    "updated_at" => now(),
                    "updated_by" => $userId,
                ]);

            foreach($selected as $subSectionId) {

                DB::table("companies_sub_sections")->updateOrInsert(
                    ["company_id" => $companyId, "sub_section_id" => $subSectionId],
                    [
                        "status" => "active",
                        "updated_at" => now(),
                        "updated_by" => $userId,
                    ]
                );

            }

            CompanySectionService::revokeDisabledRolePermissions($companyId, $selected->values()->all());

            CompanySectionService::clearCompanyCache($companyId);

        });

    }

    private static function protectedModuleIds() {

        return SubSection::query()
            ->whereIn("dom_route", self::PROTECTED_ROUTES)
            ->pluck("id")
            ->map(fn($id) => (int) $id);

    }
}
