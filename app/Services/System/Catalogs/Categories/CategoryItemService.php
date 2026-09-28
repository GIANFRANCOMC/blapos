<?php

declare(strict_types=1);

namespace App\Services\System\Catalogs\Categories;

use App\Models\System\Catalogs\{CategoryItem};

class CategoryItemService {
    public static function sync(int $itemId, array $categories, int $userId): void {

        $timestamp = now();

        CategoryItem::where("item_id", $itemId)
            ->where("status", "active")
            ->update([
                "status" => "inactive",
                "updated_at" => $timestamp,
                "updated_by" => $userId,
            ]);

        $records = collect($categories)
            ->pluck("category_id")
            ->filter(fn($categoryId): bool => is_numeric($categoryId) && (int) $categoryId > 0)
            ->map(fn($categoryId): int => (int) $categoryId)
            ->unique()
            ->map(fn(int $categoryId): array => [
                "category_id" => $categoryId,
                "item_id" => $itemId,
                "status" => "active",
                "updated_at" => $timestamp,
                "updated_by" => $userId,
            ])
            ->values()
            ->all();

        if($records === []) {

            return;

        }

        CategoryItem::query()->upsert(
            $records,
            ["category_id", "item_id"],
            ["status", "updated_at", "updated_by"]
        );

    }
}
