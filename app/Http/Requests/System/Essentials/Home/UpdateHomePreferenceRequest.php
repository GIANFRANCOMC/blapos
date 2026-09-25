<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Essentials\Home;

use App\Models\System\Organizations\{CompanySubSection};
use Illuminate\Foundation\Http\{FormRequest};

class UpdateHomePreferenceRequest extends FormRequest {
    private const HIDDEN_HOME_DIRECTORY_SLUGS = ["sc_home", "sc_dashboard"];

    public function authorize(): bool {

        $user = $this->user();
        $subSectionId = (int) $this->route("id");

        if(!$user) {

            return false;

        }

        if($subSectionId === 0) {

            return true;

        }

        return CompanySubSection::query()
            ->where("sub_section_id", $subSectionId)
            ->where("status", "active")
            ->whereHas("subSection", function($query) {

                $query->where("status", "active")
                    ->whereNotIn("slug", self::HIDDEN_HOME_DIRECTORY_SLUGS);

            })
            ->exists();

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
