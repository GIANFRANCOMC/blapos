<?php

declare(strict_types=1);

namespace App\Services\System\Database;

use App\Services\System\Organizations\Companies\{CompanySectionService};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\{Collection};
use RuntimeException;

/**
 * Projects the navigation already stored in the database to companies and
 * full-access roles. It never defines or overwrites the navigation catalog.
 */
final class SystemCatalogSyncService {
    public function sync(?int $companyId = null): array {

        return DB::transaction(function() use ($companyId): array {

            $categories = DB::table("menu_categories")->where("status", "active")->orderBy("order")->get();
            $sections = DB::table("sections")->where("status", "active")->orderBy("order")->get();
            $groups = DB::table("menu_groups")->where("status", "active")->orderBy("order")->get();
            $items = DB::table("sub_sections")->where("status", "active")->orderBy("order")->get();

            if($categories->isEmpty() || $sections->isEmpty() || $items->isEmpty()) {

                throw new RuntimeException("El catálogo de navegación no existe en la base de datos. Ejecuta SystemNavigationSeeder.");

            }

            $companyIds = $companyId
                ? collect([$companyId])
                : DB::table("companies")->pluck("id");

            foreach($companyIds as $id) {

                $this->syncCompanyAccess((int) $id, $categories, $sections, $items);
                CompanySectionService::clearCompanyCache((int) $id);

            }

            return [
                "categories" => $categories->count(),
                "sections" => $sections->count(),
                "groups" => $groups->count(),
                "items" => $items->count(),
                "companies" => $companyIds->count(),
            ];

        });

    }

    private function syncCompanyAccess(
        int $companyId,
        Collection $categories,
        Collection $sections,
        Collection $items
    ): void {

        if(!DB::table("companies")->where("id", $companyId)->exists()) {

            throw new RuntimeException("No existe la organización {$companyId}.");

        }

        $categoryOrders = $categories->pluck("order", "id");

        $sectionOrders = $sections->mapWithKeys(function(object $section) use ($categoryOrders): array {

            $categoryOrder = (int) ($categoryOrders[$section->menu_category_id] ?? 999);

            return [$section->id => ($categoryOrder * 100) + (int) $section->order];

        });

        foreach($items as $item) {

            $defaultStatus = (bool) $item->is_enabled_by_default ? "active" : "inactive";

            DB::table("companies_sub_sections")->updateOrInsert(
                ["company_id" => $companyId, "sub_section_id" => $item->id],
                [
                    "section_order" => $sectionOrders[$item->section_id] ?? 999,
                    "sub_section_order" => $item->order,
                    "status" => $defaultStatus,
                    "updated_at" => now(),
                ]
            );

        }

        if(!Schema::hasTable("role_sub_sections")) {

            return;

        }

        $fullAccessRoleIds = DB::table("roles")
            ->where("is_full_access", true)
            ->pluck("id");

        foreach($fullAccessRoleIds as $roleId) {

            foreach($items as $item) {

                DB::table("role_sub_sections")->updateOrInsert(
                    ["role_id" => $roleId, "sub_section_id" => $item->id],
                    ["status" => "active", "updated_at" => now()]
                );

            }

        }

    }
}
