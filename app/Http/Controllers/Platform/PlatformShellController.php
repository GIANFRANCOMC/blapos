<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\{Controller};
use App\Models\System\Tenancy\{TenantDatabase};
use Illuminate\View\{View};

final class PlatformShellController extends Controller {

    public function __invoke(?TenantDatabase $tenant = null): View {

        return view("Platform.shell", [
            "initialTenantId" => $tenant?->public_id,
        ]);

    }

}
