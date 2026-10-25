<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\System\NavigationMenuGroups;
use PHPUnit\Framework\TestCase;

final class NavigationMenuGroupsTest extends TestCase {

    public function test_ungrouped_pages_appear_first_then_groups_follow_their_order(): void {

        $later = (object) ["id" => 20, "order" => 2];
        $earlier = (object) ["id" => 10, "order" => 1];
        $pages = collect([
            (object) ["name" => "Grupo posterior", "menuGroup" => $later],
            (object) ["name" => "Sin grupo A", "menuGroup" => null],
            (object) ["name" => "Grupo anterior", "menuGroup" => $earlier],
            (object) ["name" => "Sin grupo B", "menuGroup" => null],
        ]);

        $groups = NavigationMenuGroups::ordered($pages);

        $this->assertSame([0, 10, 20], $groups->keys()->all());
        $this->assertSame(["Sin grupo A", "Sin grupo B"], $groups->get(0)->pluck("name")->all());

    }

}
