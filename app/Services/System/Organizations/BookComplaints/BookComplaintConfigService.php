<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\BookComplaints;

use App\Models\System\Organizations\{BookComplaint};
use App\Services\System\Base\{BaseConfigService, CompanyReferenceDataService, MasterReferenceDataService};
use stdClass;

final class BookComplaintConfigService extends BaseConfigService {
    protected const USER_SCOPED_CACHE = true;

    protected static function getCachePrefix(): string {

        return "book_complaint";

    }

    protected static function buildConfig(int $companyId, string $page, ?int $userId = null): stdClass {

        $references = CompanyReferenceDataService::forUser($userId);

        return self::data([
            "branches" => self::data([
                "records" => $references->activeBranches(),
            ]),
            "identity_document_types" => self::data([
                "records" => MasterReferenceDataService::customerIdentityDocuments($companyId),
            ]),
            "book_complaints" => self::data([
                "types" => BookComplaint::getTypes(),
                "statuses" => BookComplaint::getStatuses(),
            ]),
        ]);

    }
}
