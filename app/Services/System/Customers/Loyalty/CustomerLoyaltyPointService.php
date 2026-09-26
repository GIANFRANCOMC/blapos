<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Loyalty;

use App\Helpers\System\{Utilities};
use App\Models\System\Sales\{SaleBody, SaleHeader};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\{Collection};

final class CustomerLoyaltyPointService {
    public static function awardForSale(
        SaleHeader $saleHeader,
        Collection $saleBodies,
        int $companyId,
        int $userId
    ): void {

        if(!self::isEnabled($companyId) || !self::hasTables()) {

            return;

        }

        if(self::saleAlreadyAwarded((int) $saleHeader->id, $companyId)) {

            return;

        }

        $rules = self::activeRules($companyId);

        if($rules->isEmpty()) {

            return;

        }

        foreach($rules as $rule) {

            $eligibleBodies = self::eligibleBodies($saleBodies, $rule);

            if($eligibleBodies->isEmpty()) {

                continue;

            }

            $calculation = self::calculatePoints($saleHeader, $eligibleBodies, $rule);

            if($calculation["points"] <= 0) {

                continue;

            }

            self::insertMovement([
                "customer_id" => (int) $saleHeader->holder_id,
                "loyalty_point_rule_id" => (int) $rule->id,
                "sale_header_id" => (int) $saleHeader->id,
                "sale_body_id" => null,
                "movement_type" => "earned",
                "basis_type" => $calculation["basis_type"],
                "basis_amount" => $calculation["basis_amount"],
                "points" => $calculation["points"],
                "description" => "Puntos generados por venta {$saleHeader->serie_sequential}.",
                "created_by" => $userId,
            ]);

        }

    }

    public static function reverseForCanceledSale(
        SaleHeader $saleHeader,
        int $companyId,
        int $userId
    ): void {

        if(!self::hasTables()) {

            return;

        }

        $enabled = (bool) CompanySettingService::value(
            CompanySettingService::LOYALTY,
            "reverse_points_on_sale_cancellation",
            true
        );

        if(!$enabled || self::saleAlreadyReversed((int) $saleHeader->id, $companyId)) {

            return;

        }

        $earnedPoints = (float) DB::table("customer_point_movements")
            ->where("sale_header_id", (int) $saleHeader->id)
            ->where("movement_type", "earned")
            ->where("status", "active")
            ->sum("points");

        if($earnedPoints <= 0) {

            return;

        }

        self::insertMovement([
            "customer_id" => (int) $saleHeader->holder_id,
            "loyalty_point_rule_id" => null,
            "sale_header_id" => (int) $saleHeader->id,
            "sale_body_id" => null,
            "movement_type" => "reversal",
            "basis_type" => "manual",
            "basis_amount" => $earnedPoints,
            "points" => -abs($earnedPoints),
            "description" => "Reverso de puntos por anulación de venta {$saleHeader->serie_sequential}.",
            "created_by" => $userId,
        ]);

        DB::table("customer_point_movements")
            ->where("sale_header_id", (int) $saleHeader->id)
            ->where("movement_type", "earned")
            ->update([
                "status" => "canceled",
                "updated_at" => now(),
                "updated_by" => $userId,
            ]);

    }

    private static function calculatePoints(SaleHeader $saleHeader, Collection $saleBodies, object $rule): array {

        $triggerType = (string) $rule->trigger_type;

        if($triggerType === "item_quantity" || $triggerType === "subscription_sale") {

            $quantity = Utilities::round((float) $saleBodies->sum("quantity"));

            return [
                "basis_type" => "item_quantity",
                "basis_amount" => $quantity,
                "points" => Utilities::round($quantity * (float) $rule->points_per_unit),
            ];

        }

        $basisAmount = Utilities::round((float) $saleBodies->sum("total"));

        if($basisAmount < (float) $rule->minimum_sale_total) {

            return ["basis_type" => "sale_total", "basis_amount" => $basisAmount, "points" => 0];

        }

        $amountStep = max(0.0001, (float) $rule->amount_step);

        $steps = floor($basisAmount / $amountStep);

        return [
            "basis_type" => "sale_total",
            "basis_amount" => $basisAmount,
            "points" => Utilities::round($steps * (float) $rule->points_per_amount),
        ];

    }

    private static function eligibleBodies(Collection $saleBodies, object $rule): Collection {

        $scope = (string) $rule->apply_scope;

        if($scope === "selected_items") {

            $itemIds = DB::table("loyalty_point_rule_items")
                ->where("loyalty_point_rule_id", (int) $rule->id)
                ->where("status", "active")
                ->pluck("item_id")
                ->map(fn($id) => (int) $id)
                ->all();

            return $saleBodies->filter(fn(SaleBody $body) => in_array((int) $body->item_id, $itemIds, true));

        }

        if($scope === "all") {

            return $saleBodies;

        }

        return $saleBodies->where("type", $scope)->values();

    }

    private static function insertMovement(array $data): void {

        DB::table("customer_point_movements")->insert([
            "customer_id" => $data["customer_id"],
            "loyalty_point_rule_id" => $data["loyalty_point_rule_id"],
            "sale_header_id" => $data["sale_header_id"],
            "sale_body_id" => $data["sale_body_id"],
            "movement_type" => $data["movement_type"],
            "basis_type" => $data["basis_type"],
            "basis_amount" => $data["basis_amount"],
            "points" => $data["points"],
            "description" => $data["description"],
            "occurred_at" => now(),
            "status" => "active",
            "created_at" => now(),
            "created_by" => $data["created_by"],
        ]);

        DB::table("customer_point_balances")->updateOrInsert(
            [
                "customer_id" => $data["customer_id"],
            ],
            [
                "customer_id" => $data["customer_id"],
                "updated_at" => now(),
                "updated_by" => $data["created_by"],
            ]
        );

        DB::table("customer_point_balances")
            ->where("customer_id", $data["customer_id"])
            ->increment("points_balance", (float) $data["points"], [
                "updated_at" => now(),
                "updated_by" => $data["created_by"],
            ]);

    }

    private static function isEnabled(int $companyId): bool {

        return (bool) CompanySettingService::value(
            CompanySettingService::LOYALTY,
            "enabled",
            false
        );

    }

    private static function activeRules(int $companyId): Collection {

        $now = now();

        return DB::table("loyalty_point_rules")
            ->where("status", "active")
            ->where(function($query) use ($now) {

                $query->whereNull("starts_at")
                    ->orWhere("starts_at", "<=", $now);

            })
            ->where(function($query) use ($now) {

                $query->whereNull("ends_at")
                    ->orWhere("ends_at", ">=", $now);

            })
            ->orderBy("id")
            ->get();

    }

    private static function saleAlreadyAwarded(int $saleHeaderId, int $companyId): bool {

        return DB::table("customer_point_movements")
            ->where("sale_header_id", $saleHeaderId)
            ->where("movement_type", "earned")
            ->exists();

    }

    private static function saleAlreadyReversed(int $saleHeaderId, int $companyId): bool {

        return DB::table("customer_point_movements")
            ->where("sale_header_id", $saleHeaderId)
            ->where("movement_type", "reversal")
            ->exists();

    }

    private static function hasTables(): bool {

        return Schema::hasTable("customer_point_movements")
            && Schema::hasTable("customer_point_balances")
            && Schema::hasTable("loyalty_point_rules");

    }
}
