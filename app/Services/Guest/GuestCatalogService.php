<?php

declare(strict_types=1);

namespace App\Services\Guest;

use App\Models\Guest\{Category, Item};
use Illuminate\Database\Eloquent\{Collection};

/**
 * Provides the public catalog exposed to a company's visitors.
 */
final class GuestCatalogService {
    public static function publicItems(): Collection {

        return Item::query()
            ->select([
                "id",
                "name",
                "description",
                "price",
                "min_price",
                "max_price",
                "currency_id",
                "type",
                "duration_type",
                "duration_value",
                "see_my_web_price",
                "status",
            ])
            ->where("see_my_web", true)
            ->where("status", "active")
            ->with(["currency", "categories"])
            ->orderByDesc("type")
            ->orderBy("name")
            ->get();

    }

    public static function publicCategories(): Collection {

        return Category::query()
            ->where("is_public", true)
            ->where("status", "active")
            ->orderBy("sort_order")
            ->orderBy("name")
            ->get();

    }
}
