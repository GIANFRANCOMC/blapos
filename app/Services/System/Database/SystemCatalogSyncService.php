<?php

declare(strict_types=1);

namespace App\Services\System\Database;

use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use App\Support\System\{NavigationOrder};
use Illuminate\Support\Facades\{DB, Route, Schema};
use Illuminate\Support\{Collection};
use RuntimeException;

/**
 * Projects the navigation already stored in the database to companies and
 * full-access roles. Layout changes require an explicit application.
 */
final class SystemCatalogSyncService {

    public function sync(): array {

        return DB::transaction(fn(): array => $this->projectNavigation());

    }

    public function applyLayout(array $layout): array {

        return DB::transaction(function() use ($layout): array {

            $groups = array_map(fn(array $group): array => [
                "id" => $group["id"],
                "section_id" => $group["section_id"],
                "slug" => $group["code"],
                "name" => $group["label"],
                "dom_icon" => $group["dom_icon"],
                "order" => $group["order"],
            ], $layout["groups"]);

            $this->writeLayout("sections", $layout["sections"], [
                "menu_category_id", "order", "dom_id", "dom_label", "dom_icon", "description", "has_sub_menu",
            ]);
            $this->writeLayout("menu_groups", $groups, ["section_id", "name", "dom_icon", "order"]);
            $this->stageRouteChanges($layout["items"]);
            $this->writeLayout("sub_sections", $layout["items"], [
                "section_id", "menu_group_id", "order", "dom_id", "dom_label", "dom_route", "description", "dom_icon",
            ], ["is_enabled_by_default" => true]);
            foreach($layout["retired_items"] ?? [] as $id => $slug) {
                DB::table("sub_sections")->where("id", $id)->where("slug", $slug)
                    ->where("status", "active")
                    ->update(["status" => "inactive", "is_enabled_by_default" => false, "updated_at" => now()]);
            }

            $this->releasePendingItems($layout["released_items"] ?? []);
            $this->retireEmptyGroups($layout["retired_groups"] ?? []);
            $this->retireEmptySections($layout["retired_sections"] ?? []);

            return $this->projectNavigation(array_column($layout["items"], "id"));

        });

    }

    private function releasePendingItems(array $releasedItems): void {

        foreach($releasedItems as $id => $slug) {
            $item = DB::table("sub_sections")->where("id", $id)->where("slug", $slug)
                ->where("status", "inactive")->where("is_enabled_by_default", false);
            $route = (clone $item)->value("dom_route");

            if($route !== null && Route::has($route)) {
                $item->update(["status" => "active", "is_enabled_by_default" => true, "updated_at" => now()]);
            }
        }

    }

    private function retireEmptyGroups(array $retiredGroups): void {

        foreach($retiredGroups as $id => $slug) {

            DB::table("menu_groups")->where("id", $id)->where("slug", $slug)
                ->where("status", "active")
                ->whereNotExists(fn($query) => $query->selectRaw("1")->from("sub_sections")
                    ->whereColumn("sub_sections.menu_group_id", "menu_groups.id"))
                ->update(["status" => "inactive", "updated_at" => now()]);

        }

    }

    private function retireEmptySections(array $retiredSections): void {

        foreach($retiredSections as $id => $slug) {

            $section = DB::table("sections")->where("id", $id)->where("slug", $slug)
                ->where("status", "active");

            if($section->lockForUpdate()->first(["id"]) === null) {
                continue;
            }

            if(DB::table("menu_groups")->where("section_id", $id)->where("status", "active")->exists()
                || DB::table("sub_sections")->where("section_id", $id)->exists()) {
                throw new RuntimeException("La sección {$slug} conserva grupos o páginas. Reubícalos antes de aplicar la distribución.");
            }

            $section->update(["status" => "inactive", "updated_at" => now()]);

        }

    }

    private function stageRouteChanges(array $items): void {

        $desired = array_column($items, "dom_route", "id");
        $current = DB::table("sub_sections")
            ->whereIn("id", array_keys($desired))
            ->orWhereIn("dom_route", array_values($desired))
            ->lockForUpdate()
            ->get(["id", "dom_route"]);
        $moving = [];

        foreach($current as $record) {

            if(!isset($desired[$record->id])) {
                throw new RuntimeException("La ruta {$record->dom_route} pertenece a un módulo ajeno a esta distribución.");
            }

            if($desired[$record->id] !== $record->dom_route) {
                $moving[] = $record->id;
            }

        }

        if($moving !== []) {
            // Libera las rutas que cambian de módulo dentro de la misma transacción.
            DB::table("sub_sections")->whereIn("id", $moving)
                ->update(["dom_route" => DB::raw("CONCAT('navigation.layout.', id)")]);
        }

    }

    private function writeLayout(string $table, array $records, array $columns, array $defaults = []): void {

        $slugs = array_column($records, "slug", "id");
        $existing = DB::table($table)
            ->whereIn("id", array_keys($slugs))
            ->orWhereIn("slug", array_values($slugs))
            ->lockForUpdate()
            ->get(["id", "slug"]);

        foreach($existing as $record) {

            if(($slugs[$record->id] ?? null) !== $record->slug) {
                throw new RuntimeException("La clave de navegación {$record->slug} no coincide con el catálogo de {$table}.");
            }

        }

        $timestamp = now();
        $rows = array_map(fn(array $record): array => $record + $defaults + [
            "status" => "active",
            "created_at" => $timestamp,
            "updated_at" => $timestamp,
        ], $records);
        DB::table($table)->upsert($rows, ["id"], [...$columns, "updated_at"]);

    }

    private function projectNavigation(array $reorderedIds = []): array {

        $categories = DB::table("menu_categories")->where("status", "active")->orderBy("order")->get();
        $sections = DB::table("sections")->where("status", "active")->orderBy("order")->get();
        $groups = DB::table("menu_groups")->where("status", "active")->orderBy("order")->get();
        $items = DB::table("sub_sections")->where("status", "active")->orderBy("order")->get();

        if($categories->isEmpty() || $sections->isEmpty() || $items->isEmpty()) {

            throw new RuntimeException("El catálogo de navegación no existe en la base de datos. Ejecuta SystemNavigationSeeder.");

        }

        $this->syncCompanyAccess($categories, $sections, $groups, $items, $reorderedIds);
        DB::afterCommit(fn() => CompanySectionService::clearTenantCache());

        return [
            "categories" => $categories->count(),
            "sections" => $sections->count(),
            "groups" => $groups->count(),
            "items" => $items->count(),
        ];

    }

    private function syncCompanyAccess(
        Collection $categories,
        Collection $sections,
        Collection $groups,
        Collection $items,
        array $reorderedIds
    ): void {

        $companyId = app(TenantCompanyContext::class)->id();

        $categoryOrders = $categories->pluck("order", "id");

        $sectionOrders = $sections->mapWithKeys(function(object $section) use ($categoryOrders): array {

            $categoryOrder = (int) ($categoryOrders[$section->menu_category_id] ?? 999);

            return [$section->id => NavigationOrder::section($categoryOrder, (int) $section->order)];

        });

        $groupOrders = $groups->pluck("order", "id");

        $timestamp = now();
        $companyModules = $items
            ->map(function(object $item) use ($companyId, $sectionOrders, $groupOrders, $timestamp): array {

                $groupOrder = $item->menu_group_id === null
                    ? null
                    : $groupOrders->get($item->menu_group_id);

                return [
                    "company_id" => $companyId,
                    "sub_section_id" => (int) $item->id,
                    "section_order" => $sectionOrders[$item->section_id] ?? 999,
                    "sub_section_order" => NavigationOrder::item(
                        $groupOrder === null ? null : (int) $groupOrder,
                        (int) $item->order
                    ),
                    "status" => (bool) $item->is_enabled_by_default ? "active" : "inactive",
                    "updated_at" => $timestamp,
                ];

            })
            ->all();

        DB::table("companies_sub_sections")->insertOrIgnore($companyModules);

        if($reorderedIds !== []) {

            $reordered = array_filter(
                $companyModules,
                fn(array $module): bool => in_array($module["sub_section_id"], $reorderedIds, true)
            );
            DB::table("companies_sub_sections")->upsert(
                $reordered,
                ["company_id", "sub_section_id"],
                ["section_order", "sub_section_order"]
            );

        }

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

        DB::table("role_sub_sections")->insertOrIgnore($roleModules);

    }

}
