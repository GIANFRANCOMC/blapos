<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Base;

use App\Http\Controllers\System\Concerns\{HandlesApiResponses, HandlesExceptions};
use App\Http\Controllers\{Controller};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Http\{Request};
use Illuminate\Support\Facades\{Auth};

/**
 * Base Controller for System Controllers
 * Provides common functionality for all system controllers
 */
abstract class BaseController extends Controller {
    use HandlesApiResponses, HandlesExceptions;

    /**
     * Get authenticated user
     *
     * @return \App\Models\System\Organizations\User
     */
    protected function getAuthUser() {

        return Auth::user();

    }

    /**
     * Get authenticated user's company ID
     */
    protected function getCompanyId(): int {

        return app(TenantCompanyContext::class)->id();

    }

    /**
     * Get authenticated user's ID
     */
    protected function getUserId(): int {

        return $this->getAuthUser()->id;

    }

    /**
     * Get per page value from request
     *
     * @param  int  $default Default per page
     */
    protected function getPerPage(Request $request, int $default = 15): int {

        return intval($request->input("per_page", $default));

    }

    /**
     * Get filters from request
     */
    protected function getFilters(Request $request): array {

        return [
            "filter_by" => $request->input("filter_by"),
            "word" => $request->input("word"),
        ];

    }

    /**
     * Get page identifier from request
     */
    protected function getPage(Request $request): string {

        return $request->input("page", "");

    }
}
