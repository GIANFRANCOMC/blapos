<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Services\System\Tenancy\{TenantCompanyContext};

final class CompanyCardDataService {

    public function __construct(
        private readonly CompanyLogomarkService $logomarkService,
        private readonly TenantCompanyContext $companyContext
    ) {
    }

    public function current(): array {

        $company = $this->companyContext->get();
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
