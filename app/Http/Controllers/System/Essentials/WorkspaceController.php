<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Essentials\{UserNavigationService};
use App\Services\System\Organizations\Companies\{CompanyCardDataService, CompanySectionService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use App\Services\System\Tenancy\{PlatformSupportService};
use Illuminate\Contracts\View\{View};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class WorkspaceController extends BaseController {

    public function __construct(
        private readonly UserNavigationService $navigationService,
        private readonly CompanyCardDataService $companyCardDataService
    ) {

    }

    public function index(): View {

        return view("System/general/Essentials/workspace/main");

    }

    public function data(): JsonResponse {

        $user = $this->getAuthUser();
        $workspace = $this->navigationService->getWorkspace($user);
        $firstName = Str::before(trim($user->name), " ");
        $company = app(TenantCompanyContext::class)->get();
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
            "company" => $this->companyCardDataService->forCompany($company),
            "support" => $supportContact,
        ]);

    }

    protected function getTranslationNamespace(): string {

        return "System.Essentials.workspace";

    }

}
