<?php

declare(strict_types=1);

namespace App\Services\System\Tenancy;

use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Organizations\Companies\{CompanySectionService};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Collection};
use RuntimeException;

final class PlatformTenantService {
    public function __construct(private readonly TenantConnectionManager $connections) {
    }

    public function modules(TenantDatabase $tenant): Collection {

        $this->connections->connect($tenant);

        try {

            $companyId = $this->rootCompanyId();

            $latestCompanyModules = DB::table("companies_sub_sections")
                ->selectRaw("MAX(id) as id, sub_section_id")
                ->where("company_id", $companyId)
                ->groupBy("sub_section_id");

            return DB::table("sub_sections as ss")
                ->join("sections as s", "s.id", "=", "ss.section_id")
                ->join("menu_categories as mc", "mc.id", "=", "s.menu_category_id")
                ->leftJoin("menu_groups as mg", "mg.id", "=", "ss.menu_group_id")
                ->leftJoinSub($latestCompanyModules, "latest_css", "latest_css.sub_section_id", "=", "ss.id")
                ->leftJoin("companies_sub_sections as css", "css.id", "=", "latest_css.id")
                ->where("ss.status", "active")
                ->orderBy("mc.order")
                ->orderBy("s.order")
                ->orderByRaw("COALESCE(mg.`order`, 0)")
                ->orderBy("ss.order")
                ->get([
                    "ss.id", "ss.dom_label", "ss.dom_route", "ss.order",
                    "s.id as section_id", "s.dom_label as section_name", "s.order as section_order",
                    "mc.name as category_name", "mc.order as category_order",
                    "mg.name as group_name",
                    "css.status as company_status",
                ]);

        }finally {

            $this->connections->disconnect();

        }

    }

    public function updateModules(TenantDatabase $tenant, array $enabledModuleIds): int {

        $this->connections->connect($tenant);

        try {

            $companyId = $this->rootCompanyId();

            $enabled = collect($enabledModuleIds)->map(fn($id) => (int) $id)->unique();

            $modules = DB::table("sub_sections as ss")
                ->join("sections as s", "s.id", "=", "ss.section_id")
                ->join("menu_categories as mc", "mc.id", "=", "s.menu_category_id")
                ->where("ss.status", "active")
                ->get([
                    "ss.id", "ss.order as item_order", "s.order as section_order",
                    "mc.order as category_order",
                ]);

            $now = now();
            $records = $modules->map(fn($module) => [
                "company_id" => $companyId,
                "sub_section_id" => (int) $module->id,
                "section_order" => ((int) $module->category_order * 100) + (int) $module->section_order,
                "sub_section_order" => (int) $module->item_order,
                "status" => $enabled->contains((int) $module->id) ? "active" : "inactive",
                "created_at" => $now,
                "updated_at" => $now,
            ])->all();

            DB::transaction(function() use ($companyId, $enabled, $modules, $records): void {

                DB::table("companies")
                    ->where("id", $companyId)
                    ->lockForUpdate()
                    ->value("id");

                DB::table("companies_sub_sections")
                    ->where("company_id", $companyId)
                    ->delete();

                if($records !== []) {

                    DB::table("companies_sub_sections")->insert($records);

                }

                CompanySectionService::revokeDisabledRolePermissions(
                    $companyId,
                    $enabled->intersect($modules->pluck("id"))->values()->all()
                );

            });

            CompanySectionService::clearCompanyCache($companyId);

            return $enabled->intersect($modules->pluck("id")->map(fn($id) => (int) $id))->count();

        }finally {

            $this->connections->disconnect();

        }

    }

    private function rootCompanyId(): int {

        $companyIds = DB::table("companies")
            ->orderBy("id")
            ->limit(2)
            ->pluck("id");

        if($companyIds->count() !== 1) {

            throw new RuntimeException("El tenant debe contener exactamente una empresa raíz.");

        }

        return (int) $companyIds->first();

    }
}
