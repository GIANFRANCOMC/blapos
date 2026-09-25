<?php

declare(strict_types=1);

namespace App\Enums\System\Tenancy;

enum TenantStatus: string {
    case PROVISIONING = "provisioning";

    case ACTIVE = "active";

    case INACTIVE = "inactive";

    case SUSPENDED = "suspended";

    case PROVISIONING_FAILED = "provisioning_failed";

    case MAINTENANCE = "maintenance";

    public static function values(): array {

        return array_column(self::cases(), "value");

    }

    public static function manuallyAssignableValues(): array {

        return [
            self::ACTIVE->value,
            self::INACTIVE->value,
            self::SUSPENDED->value,
            self::MAINTENANCE->value,
        ];

    }

    public function acceptsTraffic(): bool {

        return $this === self::ACTIVE;

    }
}
