<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Essentials;

use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Essentials\Home\{UpdateHomePreferenceRequest};
use App\Models\System\Organizations\{UserPreference};
use Illuminate\Http\{JsonResponse, Request};
use stdClass;

class HomeController extends BaseController {

    /**
     * Translation namespace for home module
     */
    private const TRANSLATION_NAMESPACE = "System.Essentials.home";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $initParams = new stdClass();
        $config = new stdClass();
        $page = $this->getPage($request);

        if(in_array($page, ["main"])) {

            //

        }

        $initParams->config = $config;

        $initParams->bool = true;

        return $initParams;

    }

    /**
     * Display the home index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Essentials/home/main");

    }

    /**
     * Update user preferences
     */
    public function update(UpdateHomePreferenceRequest $request, int $id): JsonResponse {

        try {

            $user = $this->getAuthUser();
            $validated = $request->validated();

            $data = [
                "show_actions" => $validated["show_actions"],
                "show_only_favorites" => $validated["show_only_favorites"],
                "show_descriptions" => $validated["show_descriptions"],
                "records" => [],
            ];

            if($id > 0) {

                $record = ["sub_section_id" => $id];

                foreach(["is_favorite"] as $field) {

                    if(array_key_exists($field, $validated)) {

                        $record[$field] = $validated[$field];

                    }

                }

                $data["records"][] = $record;

            }

            $updateItems = UserPreference::updateItems($user->id, "config_companies_sub_sections", $data);

            if($updateItems["bool"]) {

                $user->unsetRelation("preferences");

                return $this->successResponse(["preferences" => $user->fresh()->formatted_preferences], "updated");

            }

            return $this->errorResponse("update_failed");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Get translation namespace for home module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

}
