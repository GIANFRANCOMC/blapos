<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Users;

use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService};
use stdClass;

final class UserAttendanceConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "user_attendance";

    }

    protected static function buildConfig(string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::forUser($userId);

        return self::data([
            "branches" => $references->activeBranches(),
            "users" => $references->userOptions(),
            "sourceTypes" => collect(UserAttendanceService::sourceTypes())->map(fn($source) => [
                "code" => $source,
                "label" => match ($source) {
                    "manual_form" => "Manual",
                    "qr_camera" => "Cámara QR",
                    "qr_scanner" => "Lector QR",
                    "biometric" => "Biométrico",
                    default => "Sistema"
                },
            ])->values(),
            "statuses" => [
                ["code" => "active", "label" => "En curso"],
                ["code" => "finalized", "label" => "Finalizada"],
                ["code" => "canceled", "label" => "Cancelada"],
            ],
        ]);

    }
}
