<?php

declare(strict_types=1);

namespace App\Helpers\System;

enum StockStatus: string {

    case Empty = "empty";
    case Low = "low";
    case Healthy = "healthy";

    public static function fromLevels(mixed $quantity, mixed $minimum): self {

        return match (true) {
            Numbers::compare($quantity, 0) <= 0 => self::Empty,
            Numbers::compare($quantity, $minimum) <= 0 => self::Low,
            default => self::Healthy,
        };

    }

    public static function forProduct(mixed $total, bool $hasWarehouseAlerts): self {

        return self::fromLevels($total, 0) === self::Empty
            ? self::Empty
            : ($hasWarehouseAlerts ? self::Low : self::Healthy);

    }

    public function label(): string {

        static $labels;
        $labels ??= json_decode(file_get_contents(resource_path("stock-statuses.json")), true, flags: JSON_THROW_ON_ERROR);

        return $labels[$this->value];

    }

}
