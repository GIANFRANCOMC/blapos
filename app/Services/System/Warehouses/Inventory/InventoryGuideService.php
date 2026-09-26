<?php

declare(strict_types=1);

namespace App\Services\System\Warehouses\Inventory;

use App\Helpers\System\{Utilities};
use App\Models\System\Warehouses\{InventoryGuide, InventoryGuideItem};
use Illuminate\Support\Facades\{DB};
use Illuminate\Support\{Str};

final class InventoryGuideService {
    public static function create(int $userId, array $data): InventoryGuide {

        return DB::transaction(function() use ($userId, $data) {

            $number = self::nextNumber((string) $data["guide_type"]);
            $guide = InventoryGuide::create([
                "warehouse_id" => $data["warehouse_id"],
                "number" => $number,
                "guide_type" => $data["guide_type"],
                "issue_date" => $data["issue_date"],
                "reason" => $data["reason"],
                "reference" => $data["reference"] ?? null,
                "status" => "confirmed",
                "confirmed_at" => now(),
                "confirmed_by" => $userId,
            ]);

            foreach($data["items"] as $detail) {

                $movement = InventoryMovementService::apply([
                    "warehouse_id" => (int) $data["warehouse_id"],
                    "item_id" => (int) $detail["item_id"],
                    "user_id" => $userId,
                    "movement_type" => $data["guide_type"],
                    "origin_type" => "inventory_guide",
                    "origin_id" => $guide->id,
                    "quantity" => (float) $detail["quantity"],
                    "unit_cost" => $data["guide_type"] === "entry"
                        ? ($detail["unit_cost"] ?? null)
                        : null,
                    "reason" => $data["reason"],
                    "reference" => $number,
                    "metadata" => ["inventory_guide_id" => $guide->id],
                ]);

                InventoryGuideItem::create([
                    "inventory_guide_id" => $guide->id,
                    "item_id" => $detail["item_id"],
                    "inventory_movement_id" => $movement->id,
                    "quantity" => $detail["quantity"],
                    "unit_cost" => $movement->unit_cost,
                ]);

            }

            return $guide->load(["warehouse.branch", "items.item", "items.movement", "confirmedBy"]);

        });

    }

    public static function query(array $filters = []) {

        return InventoryGuide::query()
            ->with(["warehouse.branch", "items.item", "confirmedBy"])
            ->when($filters["warehouse_id"] ?? null, fn($query, $id) => $query->where("warehouse_id", $id))
            ->when($filters["guide_type"] ?? null, fn($query, $type) => $query->where("guide_type", $type))
            ->when($filters["date_from"] ?? null, fn($query, $date) => $query->where("issue_date", ">=", Utilities::startOfDay($date)))
            ->when($filters["date_to"] ?? null, fn($query, $date) => $query->where("issue_date", "<=", Utilities::endOfDay($date)))
            ->orderByDesc("id");

    }

    private static function nextNumber(string $type): string {

        $prefix = $type === "entry" ? "GE" : "GS";

        do {

            $number = $prefix."-".now()->format("Ymd")."-".strtoupper(Str::random(6));

        } while(InventoryGuide::query()
            ->where("number", $number)
            ->exists());

        return $number;

    }
}
