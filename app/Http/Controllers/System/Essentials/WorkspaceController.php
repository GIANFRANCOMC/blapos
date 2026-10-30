<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Essentials\{UserNavigationService};
use App\Services\System\Organizations\Companies\{CompanyLogomarkService, CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use App\Services\System\Tenancy\{PlatformSupportService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Str;

final class WorkspaceController extends BaseController {

    public function __construct(
        private readonly UserNavigationService $navigationService,
        private readonly CompanyLogomarkService $companyLogomarkService
    ) {

    }

    public function index(Request $request): View|JsonResponse {

        if(!$request->expectsJson()) {

            return view("System/general/Essentials/workspace/main");

        }

        $user = $this->getAuthUser();
        $workspace = $this->navigationService->getWorkspace($user);
        $firstName = Str::before(trim($user->name), " ");
        $company = app(TenantCompanyContext::class)->get()->loadMissing("identityDocumentType", "socialsMedia");
        $companySocials = $company->socialsMedia->keyBy("type");
        $companyName = $company->commercial_name ?: $company->legal_name ?: "Empresa";
        $logomarkUrl = $this->companyLogomarkService->url($company->logomark);
        $supportContact = app(PlatformSupportService::class)->contact();
        $breadcrumbSection = CompanySectionService::getSections($user->role?->id)
            ->first(fn($section) => $section->subSections->contains("dom_route", "workspace.index"));
        $breadcrumbPage = $breadcrumbSection?->subSections->firstWhere("dom_route", "workspace.index");

        return response()->json([
            "first_name" => $firstName,
            "actions" => $workspace["actions"]->values(),
            "breadcrumb" => [
                "section" => $breadcrumbSection?->dom_label,
                "page" => $breadcrumbPage?->dom_label,
            ],
            "company" => [
                "name" => $company->legal_name ?: $companyName,
                "commercial_name" => $company->commercial_name,
                "document_type" => $company->identityDocumentType?->name,
                "document_number" => $company->document_number,
                "tagline" => $company->tagline,
                "address" => $company->address,
                "email" => $company->email,
                "telephone" => $company->telephone,
                "logomark_url" => $logomarkUrl,
                "socials" => [
                    "whatsapp" => $companySocials->get("whatsapp")?->link,
                    "facebook" => $companySocials->get("facebook")?->link,
                    "instagram" => $companySocials->get("instagram")?->link,
                ],
            ],
            "support" => $supportContact,
        ]);

    }

    protected function getTranslationNamespace(): string {

        return "System.Essentials.workspace";

    }

}
