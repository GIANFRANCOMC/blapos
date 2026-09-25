<?php

declare(strict_types=1);

namespace App\Services\System\Assets;

use App\Models\System\Assets\{AssetAssignment, BranchAsset};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use stdClass;

final class AssetManagementConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "asset_management";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::forUser($userId);

        return self::data([
            "assets" => self::data([
                "records" => $references->assets(),
            ]),
            "branches" => self::data([
                "records" => $references->activeBranches(),
            ]),
            "users" => self::data([
                "records" => $references->userOptions(),
            ]),
            "branchAssets" => self::data([
                "statuses" => BranchAsset::getStatuses(),
            ]),
            "assetAssignments" => self::data([
                "statuses" => AssetAssignment::getStatuses(),
            ]),
        ]);

    }
}
