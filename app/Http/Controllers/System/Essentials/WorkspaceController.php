<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Essentials\{UserNavigationService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Contracts\View\{View};
use Illuminate\Support\Facades\{Storage};

final class WorkspaceController extends BaseController {

    public function __construct(private readonly UserNavigationService $navigationService) {

    }

    public function index(): View {

        $user = $this->getAuthUser();
        $workspace = $this->navigationService->getWorkspace($user);
        $hour = now()->hour;
        $greeting = $hour < 12 ? "Buenos días" : ($hour < 19 ? "Buenas tardes" : "Buenas noches");
        $company = app(TenantCompanyContext::class)->get()->loadMissing("identityDocumentType", "socialsMedia");
        $companySocials = $company->socialsMedia->keyBy("type");
        $companyName = $company->commercial_name ?: $company->legal_name ?: "Empresa";
        $logoPath = collect([$company->combinationmark, $company->logomark, $company->logotype])
            ->filter()
            ->first(fn(string $path): bool => Storage::disk("public")->exists($path));
        $logoUrl = $logoPath
            ? asset("storage/".$logoPath)
            : asset(config("app.owner_app")->assets->img->combinationmark);

        return view("System/general/Essentials/workspace/main", compact("user", "workspace", "greeting", "company", "companyName", "companySocials", "logoUrl"));

    }

    protected function getTranslationNamespace(): string {

        return "System.Essentials.workspace";

    }

}
