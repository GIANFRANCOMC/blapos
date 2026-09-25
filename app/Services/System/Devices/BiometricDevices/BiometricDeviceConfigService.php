<?php

declare(strict_types=1);

namespace App\Services\System\Devices\BiometricDevices;

use App\Models\System\Devices\{BiometricDevice, BiometricDeviceBrand, BiometricDeviceModel};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use stdClass;

final class BiometricDeviceConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "biometric_device";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $brands = BiometricDeviceBrand::query()
            ->where("status", "active")
            ->orderBy("name")
            ->get(["id", "slug", "name"]);

        $models = BiometricDeviceModel::query()
            ->where("status", "active")
            ->with("brand:id,name")
            ->orderBy("name")
            ->get(["id", "biometric_device_brand_id", "slug", "name"]);

        return self::data([
            "branches" => self::data([
                "records" => CompanyReferenceDataService::forUser($userId)->activeBranches(),
            ]),
            "brands" => self::data([
                "records" => $brands,
            ]),
            "models" => self::data([
                "records" => $models,
            ]),
            "statuses" => BiometricDevice::getStatuses(),
        ]);

    }
}
