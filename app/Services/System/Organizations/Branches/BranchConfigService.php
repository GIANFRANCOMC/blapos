<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Branches;

use App\Models\System\Organizations\{Branch};
use App\Services\System\Base\{BaseConfigService};
use stdClass;

final class BranchConfigService extends BaseConfigService {
    protected static function getCachePrefix(): string {

        return "branch";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        return self::data([
            "internal_code_prefixes" => self::internalCodePrefixes(),
            "statuses" => Branch::getStatuses(),
        ]);

    }
}
