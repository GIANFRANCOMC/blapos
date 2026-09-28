<?php

declare(strict_types=1);

namespace App\Console\Support;

final class TenantCommandBatchLimit {
    public static function normalize(mixed $value): int {

        return min(1000, max(1, (int) $value));

    }
}
