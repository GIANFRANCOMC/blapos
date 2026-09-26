<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Organizations;

use App\Http\Controllers\System\Base\{BaseController};
use App\Models\System\General\{SubSection};
use App\Services\System\Organizations\{BusinessProfileService};
use Illuminate\Http\{JsonResponse, Request};

final class BusinessProfileController extends BaseController {
    private const TRANSLATION_NAMESPACE = "System.Organizations.business_profile";

    public function index() {

        return view("System/general/Organizations/business_profile/main");

    }

    public function initParams(): JsonResponse {

        return response()->json([
            "bool" => true,
            "industries" => BusinessProfileService::industries(),
            "enabled_module_ids" => BusinessProfileService::enabledModuleIds(),
            "modules" => SubSection::query()
                ->with("section:id,dom_label,order")
                ->where("status", "active")
                ->orderBy("section_id")
                ->orderBy("order")
                ->get(["id", "section_id", "dom_label", "description", "dom_route"]),
        ]);

    }

    public function updateModules(Request $request): JsonResponse {

        $data = $request->validate([
            "enabled_module_ids" => ["present", "array"],
            "enabled_module_ids.*" => ["integer", "distinct"],
        ]);

        BusinessProfileService::updateModules(
            $data["enabled_module_ids"],
            $this->getUserId()
        );

        return response()->json([
            "bool" => true,
            "msg" => "Los módulos de la empresa se actualizaron correctamente.",
        ]);

    }

    public function apply(Request $request): JsonResponse {

        $request->validate([
            "business_industry_id" => ["required", "integer"],
        ]);

        BusinessProfileService::applyIndustry(
            (int) $request->input("business_industry_id"),
            $this->getUserId()
        );

        return response()->json([
            "bool" => true,
            "msg" => "Rubro aplicado. Los módulos disponibles fueron actualizados para esta empresa.",
        ]);

    }

    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
