<?php

declare(strict_types=1);

namespace App\Support\System;

use Illuminate\Support\Collection;

final class NavigationMenuGroups {

    public static function ordered(Collection $subSections): Collection {

        return $subSections
            ->groupBy(fn($subSection) => $subSection->menuGroup?->id ?? 0)
            ->sort(function(Collection $left, Collection $right): int {

                $leftGroup = $left->first()->menuGroup;
                $rightGroup = $right->first()->menuGroup;

                if($leftGroup === null) return $rightGroup === null ? 0 : -1;
                if($rightGroup === null) return 1;

                return ((int) $leftGroup->order <=> (int) $rightGroup->order)
                    ?: ((int) $leftGroup->id <=> (int) $rightGroup->id);

            });

    }

}
