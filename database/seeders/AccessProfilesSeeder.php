<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\System\Organizations\Roles\RolePermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class AccessProfilesSeeder extends Seeder {

    private const PROFILE_PERMISSIONS = [
        "vendedor" => [
            ["routes" => ["workspace.index", "home.index", "account.index", "products.index", "services.index", "subscriptions.index", "categories.index", "brands.index"], "actions" => ["view"]],
            ["routes" => ["customers.index", "sales.create", "sales.index", "sales.pos", "quotations.create", "quotations.index", "service_sessions.index"], "actions" => ["view", "create", "update", "operate"]],
            ["routes" => ["cash_sessions.index", "cash_movements.index"], "actions" => ["view", "operate"]],
        ],
        "compras" => [
            ["routes" => ["workspace.index", "home.index", "account.index", "products.index", "services.index", "subscriptions.index", "categories.index", "brands.index", "stocks_management.kardex.index", "stocks_management.valued.index"], "actions" => ["view"]],
            ["routes" => ["purchases.create", "purchases.index", "purchases.receipts.index", "suppliers.index", "stocks_management.stock.index", "stocks_management.transfers.index", "stocks_management.guides.index"], "actions" => ["view", "create", "update", "operate", "import", "export"]],
        ],
        "gestion" => [
            ["routes" => ["workspace.index", "home.index", "account.index", "reports.index", "companies.index", "branches.index", "users.index", "products.index", "services.index", "subscriptions.index", "categories.index", "brands.index", "customers.index", "sales.index", "purchases.index", "suppliers.index", "stocks_management.stock.index", "stocks_management.transfers.index", "stocks_management.guides.index", "stocks_management.kardex.index", "stocks_management.valued.index", "cash_summary.index"], "actions" => ["view", "export"]],
            ["routes" => ["branches.index", "users.index", "products.index", "services.index", "subscriptions.index", "categories.index", "brands.index", "customers.index", "sales.index", "purchases.index", "suppliers.index"], "actions" => ["view", "create", "update", "import", "export"]],
        ],
        "auditoria" => [
            ["routes" => ["workspace.index", "home.index", "account.index", "reports.index", "companies.index", "branches.index", "users.index", "customers.index", "products.index", "services.index", "subscriptions.index", "sales.index", "purchases.index", "stocks_management.stock.index", "stocks_management.transfers.index", "stocks_management.guides.index", "stocks_management.kardex.index", "stocks_management.valued.index", "cash_summary.index", "cash_movements.index"], "actions" => ["view", "export"]],
        ],
        "contador" => [
            ["routes" => ["workspace.index", "home.index", "account.index", "reports.index", "companies.index", "sales.index", "purchases.index", "accounts_receivable.index", "accounts_payable.index", "cash_registers.index", "cash_sessions.index", "cash_movements.index", "cash_summary.index", "misc_expenses.index", "stocks_management.valued.index"], "actions" => ["view", "export"]],
            ["routes" => ["accounts_receivable.index", "accounts_payable.index", "misc_expenses.index", "cash_movements.index"], "actions" => ["view", "create", "update", "operate", "export"]],
        ],
    ];

    private const PROFILE_NAMES = [
        "vendedor" => "Vendedor",
        "compras" => "Compras",
        "gestion" => "Gestión",
        "auditoria" => "Auditoría",
        "contador" => "Contador",
    ];

    public function run(?int $actorId = null): array {

        $routeIds = DB::table("sub_sections")->pluck("id", "dom_route")->all();
        $roleIds = [];

        foreach(self::PROFILE_NAMES as $slug => $name) {

            $roleId = (int) DB::table("roles")->where("slug", $slug)->value("id");

            if(!$roleId) {

                $roleId = (int) DB::table("roles")->insertGetId([
                    "slug" => $slug,
                    "name" => $name,
                    "is_full_access" => false,
                    "status" => "active",
                    "created_at" => now(),
                    "created_by" => $actorId,
                ]);

            }

            $roleIds[] = $roleId;
            $permissions = [];

            foreach(self::PROFILE_PERMISSIONS[$slug] as $group) {

                foreach($group["routes"] as $route) {

                    if(isset($routeIds[$route])) {

                        $id = (int) $routeIds[$route];
                        $permissions[$id] = array_values(array_unique(array_merge(
                            $permissions[$id] ?? [],
                            $group["actions"]
                        )));

                    }

                }

            }

            foreach($permissions as $subSectionId => $actions) {

                DB::table("role_sub_sections")->updateOrInsert(
                    ["role_id" => $roleId, "sub_section_id" => $subSectionId],
                    [
                        "actions" => json_encode($actions, JSON_THROW_ON_ERROR),
                        "status" => "active",
                        "updated_at" => now(),
                        "updated_by" => $actorId,
                    ]
                );

            }

            RolePermissionService::clearRoleCache($roleId);

        }

        $this->replaceLegacyDemoRole($roleIds);

        return $roleIds;

    }

    public static function roleIdForEmail(string $email, array $roleIds): int {

        return $roleIds[hexdec(substr(hash("sha256", strtolower($email)), 0, 8)) % count($roleIds)];

    }

    private function replaceLegacyDemoRole(array $roleIds): void {

        $legacyRoleId = (int) DB::table("roles")->where("slug", "demo-colaborador")->value("id");

        if(!$legacyRoleId) return;

        DB::table("users")
            ->where("role_id", $legacyRoleId)
            ->orderBy("id")
            ->chunkById(250, function($users) use ($roleIds, $legacyRoleId): void {

                foreach($users as $user) {

                    DB::table("users")->where("id", $user->id)->where("role_id", $legacyRoleId)->update([
                        "role_id" => self::roleIdForEmail((string) $user->email, $roleIds),
                        "updated_at" => now(),
                    ]);

                }

            });

        RolePermissionService::clearRoleCache($legacyRoleId);
        DB::table("roles")->where("id", $legacyRoleId)->delete();

    }

}
