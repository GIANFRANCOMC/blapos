<?php

declare(strict_types=1);

namespace App\Services\System\Sales;

final class SaleDeliveryPolicy {

    public const PENDING = "pending";

    public const DELIVERED = "delivered";

    public static function usesManagedDelivery(string $sourceChannel, bool $requiresWarehouse): bool {

        return $sourceChannel === "sale" && $requiresWarehouse;

    }

    public static function initialStatus(?string $requestedStatus, bool $requiresPhysicalDelivery): string {

        if(!$requiresPhysicalDelivery) {

            return self::DELIVERED;

        }

        return $requestedStatus === self::PENDING
            ? self::PENDING
            : self::DELIVERED;

    }

    public static function legacyMode(string $deliveryStatus): string {

        return $deliveryStatus === self::PENDING ? "pending" : "immediate";

    }

    public static function shouldExitInventory(string $deliveryStatus, bool $requiresWarehouse): bool {

        return $requiresWarehouse && $deliveryStatus === self::DELIVERED;

    }

    public static function shouldCreatePendingDelivery(string $deliveryStatus, bool $requiresWarehouse): bool {

        return $requiresWarehouse && $deliveryStatus === self::PENDING;

    }

}
