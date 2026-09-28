<?php

declare(strict_types=1);

namespace App\Services\System\Database;

use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\{Collection};
use RuntimeException;

/**
 * Projects the navigation already stored in the database to companies and
 * full-access roles. It never defines or overwrites the navigation catalog.
 */
final class SystemCatalogSyncService {
    public function sync(): array {

        return DB::transaction(function(): array {

            $categories = DB::table("menu_categories")->where("status", "active")->orderBy("order")->get();
            $sections = DB::table("sections")->where("status", "active")->orderBy("order")->get();
            $groups = DB::table("menu_groups")->where("status", "active")->orderBy("order")->get();
            $items = DB::table("sub_sections")->where("status", "active")->orderBy("order")->get();

            if($categories->isEmpty() || $sections->isEmpty() || $items->isEmpty()) {

                throw new RuntimeException("El catálogo de navegación no existe en la base de datos. Ejecuta SystemNavigationSeeder.");

            }

            $this->syncCompanyAccess($categories, $sections, $items);
            CompanySectionService::clearTenantCache();

            return [
                "categories" => $categories->count(),
                "sections" => $sections->count(),
                "groups" => $groups->count(),
                "items" => $items->count(),
            ];

        });

    }

    private function syncCompanyAccess(
        Collection $categories,
        Collection $sections,
        Collection $items
    ): void {

        $companyId = app(TenantCompanyContext::class)->id();

        $categoryOrders = $categories->pluck("order", "id");

        $sectionOrders = $sections->mapWithKeys(function(object $section) use ($categoryOrders): array {

            $categoryOrder = (int) ($categoryOrders[$section->menu_category_id] ?? 999);

            return [$section->id => ($categoryOrder * 100) + (int) $section->order];

        });

        $timestamp = now();
        $companyModules = $items
            ->map(fn(object $item): array => [
                "company_id" => $companyId,
                "sub_section_id" => (int) $item->id,
                "section_order" => $sectionOrders[$item->section_id] ?? 999,
                "sub_section_order" => (int) $item->order,
                "status" => (bool) $item->is_enabled_by_default ? "active" : "inactive",
                "updated_at" => $timestamp,
            ])
            ->all();

        DB::table("companies_sub_sections")->upsert(
            $companyModules,
            ["company_id", "sub_section_id"],
            ["section_order", "sub_section_order", "updated_at"]
        );

        if(!Schema::hasTable("role_sub_sections")) {

            return;

        }

        $fullAccessRoleIds = DB::table("roles")
            ->where("is_full_access", true)
            ->pluck("id");

        $roleModules = $fullAccessRoleIds
            ->flatMap(fn($roleId) => $items
                ->map(fn(object $item): array => [
                    "role_id" => (int) $roleId,
                    "sub_section_id" => (int) $item->id,
                    "status" => "active",
                    "updated_at" => $timestamp,
                ]))
            ->values()
            ->all();

        if($roleModules === []) {

            return;

        }

        DB::table("role_sub_sections")->upsert(
            $roleModules,
            ["role_id", "sub_section_id"],
            ["status", "updated_at"]
        );

    }
}
