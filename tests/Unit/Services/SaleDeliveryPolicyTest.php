<?php

namespace Tests\Unit\Services;

use App\Services\System\Sales\{SaleDeliveryPolicy};
use PHPUnit\Framework\{TestCase};

class SaleDeliveryPolicyTest extends TestCase {

    public function test_physical_sale_keeps_pending_status_independently_from_method(): void {

        $status = SaleDeliveryPolicy::initialStatus("pending", true);

        $this->assertSame("pending", $status);
        $this->assertTrue(SaleDeliveryPolicy::shouldCreatePendingDelivery($status, true));
        $this->assertFalse(SaleDeliveryPolicy::shouldExitInventory($status, true));

    }

    public function test_physical_delivered_sale_exits_inventory_without_pending_tracking(): void {

        $status = SaleDeliveryPolicy::initialStatus("delivered", true);

        $this->assertSame("delivered", $status);
        $this->assertTrue(SaleDeliveryPolicy::shouldExitInventory($status, true));
        $this->assertFalse(SaleDeliveryPolicy::shouldCreatePendingDelivery($status, true));

    }

    public function test_non_physical_sale_does_not_force_a_pending_delivery(): void {

        $status = SaleDeliveryPolicy::initialStatus("pending", false);

        $this->assertSame("delivered", $status);
        $this->assertFalse(SaleDeliveryPolicy::shouldExitInventory($status, false));
        $this->assertFalse(SaleDeliveryPolicy::shouldCreatePendingDelivery($status, false));

    }

    public function test_legacy_mode_is_only_a_compatibility_projection_of_status(): void {

        $this->assertSame("pending", SaleDeliveryPolicy::legacyMode("pending"));
        $this->assertSame("immediate", SaleDeliveryPolicy::legacyMode("delivered"));

    }

    public function test_pos_remains_outside_the_managed_delivery_flow(): void {

        $this->assertTrue(SaleDeliveryPolicy::usesManagedDelivery("sale", true));
        $this->assertFalse(SaleDeliveryPolicy::usesManagedDelivery("pos", true));
        $this->assertFalse(SaleDeliveryPolicy::usesManagedDelivery("sale", false));

    }

}
