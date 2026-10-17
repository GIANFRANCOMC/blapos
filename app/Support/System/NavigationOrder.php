<?php

declare(strict_types=1);

namespace App\Support\System;

final class NavigationOrder {

    private const SECTIONS_PER_CATEGORY = 100;
    private const ITEMS_PER_GROUP = 1000;

    public static function section(int $categoryOrder, int $sectionOrder): int {

        return ($categoryOrder * self::SECTIONS_PER_CATEGORY) + $sectionOrder;

    }

    public static function item(?int $groupOrder, int $itemOrder): int {

        return (($groupOrder ?? 0) * self::ITEMS_PER_GROUP) + $itemOrder;

    }

}
