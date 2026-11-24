<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Essentials\Home;

use App\Services\System\Organizations\Companies\{CompanySectionService};
use App\Services\System\Organizations\Roles\{RolePermissionService};
use Illuminate\Foundation\Http\{FormRequest};

class UpdateHomePreferenceRequest extends FormRequest {

    private const EXCLUDED_ROUTES = ["home.index", "favorites.index"];

    public function authorize(): bool {

        $user = $this->user();
        $subSectionId = (int) $this->route("id");

        if(!$user) {

            return false;

        }

        if($subSectionId === 0) {

            return true;

        }

        $module = CompanySectionService::getSections((int) $user->role_id)
            ->pluck("subSections")
            ->flatten(1)
            ->firstWhere("id", $subSectionId);

        return $module
            && !in_array($module->dom_route, self::EXCLUDED_ROUTES, true)
            && RolePermissionService::canAccessRoute($user, $module->dom_route);

    }

    public function rules(): array {

        return [
            "show_actions" => ["required", "boolean"],
            "show_only_favorites" => ["required", "boolean"],
            "show_descriptions" => ["required", "boolean"],
            "is_favorite" => ["sometimes", "boolean"],
        ];

    }

}
