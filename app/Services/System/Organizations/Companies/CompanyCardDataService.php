<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Models\System\Organizations\Company;

final class CompanyCardDataService {

    public function __construct(
        private readonly CompanyLogomarkService $logomarkService
    ) {
    }

    public function forCompany(Company $company): array {

        $company->loadMissing("identityDocumentType", "socialsMedia");
        $companySocials = $company->socialsMedia->keyBy("type");
        $companyName = $company->commercial_name ?: $company->legal_name ?: "Empresa";

        return [
            "name" => $company->legal_name ?: $companyName,
            "commercial_name" => $company->commercial_name,
            "document_type" => $company->identityDocumentType?->name,
            "document_number" => $company->document_number,
            "tagline" => $company->tagline,
            "address" => $company->address,
            "email" => $company->email,
            "telephone" => $company->telephone,
            "logomark_url" => $this->logomarkService->url($company->logomark),
            "socials" => [
                "whatsapp" => $companySocials->get("whatsapp")?->link,
                "facebook" => $companySocials->get("facebook")?->link,
                "instagram" => $companySocials->get("instagram")?->link,
            ],
        ];

    }

}
