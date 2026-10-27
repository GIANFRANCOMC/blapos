<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Essentials\{UserNavigationService};
use App\Services\System\Organizations\Companies\{CompanyLogomarkService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use App\Services\System\Tenancy\{PlatformSupportService};
use Illuminate\Contracts\View\{View};
use Illuminate\Support\Str;

final class WorkspaceController extends BaseController {

    public function __construct(
        private readonly UserNavigationService $navigationService,
        private readonly CompanyLogomarkService $companyLogomarkService
    ) {

    }

    public function index(): View {

        $user = $this->getAuthUser();
        $workspace = $this->navigationService->getWorkspace($user);
        $firstName = Str::before(trim($user->name), " ");
        $company = app(TenantCompanyContext::class)->get()->loadMissing("identityDocumentType", "socialsMedia");
        $companySocials = $company->socialsMedia->keyBy("type");
        $companyName = $company->commercial_name ?: $company->legal_name ?: "Empresa";
        $logomarkUrl = $this->companyLogomarkService->url($company->logomark);
        $supportContact = app(PlatformSupportService::class)->contact();

        return view("System/general/Essentials/workspace/main", compact("workspace", "firstName", "company", "companyName", "companySocials", "logomarkUrl", "supportContact"));

    }

    protected function getTranslationNamespace(): string {

        return "System.Essentials.workspace";

    }

}
