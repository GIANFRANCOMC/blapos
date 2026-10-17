<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\System\{NavigationOrder};
use PHPUnit\Framework\{TestCase};

final class NavigationOrderTest extends TestCase {

    public function test_group_order_precedes_local_item_order(): void {

        $this->assertLessThan(NavigationOrder::item(2, 1), NavigationOrder::item(1, 5));
        $this->assertLessThan(NavigationOrder::item(3, 1), NavigationOrder::item(2, 4));
        $this->assertSame(1, NavigationOrder::item(null, 1));

    }

    public function test_category_order_precedes_local_section_order(): void {

        $this->assertLessThan(NavigationOrder::section(4, 1), NavigationOrder::section(3, 2));

    }

}
