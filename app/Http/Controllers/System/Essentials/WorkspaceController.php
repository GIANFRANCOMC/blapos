<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Services\System\Essentials\{UserNavigationService};
use Illuminate\Contracts\View\{View};

final class WorkspaceController extends BaseController {

    public function __construct(private readonly UserNavigationService $navigationService) {

    }

    public function index(): View {

        $workspace = $this->navigationService->getWorkspace($this->getAuthUser());

        return view("System/general/Essentials/workspace/main", compact("workspace"));

    }

    protected function getTranslationNamespace(): string {

        return "System.Essentials.workspace";

    }

}
