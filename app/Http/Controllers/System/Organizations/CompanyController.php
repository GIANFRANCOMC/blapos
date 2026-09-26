<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Organizations\Companies\{UpdateCompanyRequest};
use App\Services\System\Organizations\Companies\{CompanyConfigService, CompanyService};
use Illuminate\Http\{JsonResponse, Request};

class CompanyController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Organizations.company";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return CompanyConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Organizations/companies/main");

    }

    /**
     * Update the specified company
     *
     * @param  int  $id Company ID
     */
    public function update(UpdateCompanyRequest $request, int $id): JsonResponse {

        try {

            $company = CompanyService::findByIdInTenant($id, null);

            if(!Utilities::isDefined($company)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareCompanyData($request);

            $files = $this->prepareCompanyFiles($request);
            $company = CompanyService::update($company, $data, $files, $this->getUserId());

            if(!Utilities::isDefined($company)) {

                return $this->errorResponse("update_failed");

            }

            CompanyConfigService::clearAllCache();

            return $this->updatedResponse($company, "updated", "company");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Prepare company data from request
     */
    private function prepareCompanyData(UpdateCompanyRequest $request): array {

        return [
            "identity_document_type_id" => $request->input("identity_document_type_id"),
            "document_number" => $request->input("document_number"),
            "legal_name" => $request->input("legal_name"),
            "commercial_name" => $request->input("commercial_name"),
            "tagline" => $request->input("tagline"),
            "description" => $request->input("description"),
            "address" => $request->input("address"),
            "telephone" => $request->input("telephone"),
            "email" => $request->input("email"),
            "facebook" => $request->input("facebook"),
            "instagram" => $request->input("instagram"),
            "whatsapp" => $request->input("whatsapp"),
        ];

    }

    /**
     * Prepare company files from request
     */
    private function prepareCompanyFiles(UpdateCompanyRequest $request): array {

        $files = [];

        $imageFields = ["logotype", "combinationmark", "logomark", "login_image"];

        foreach($imageFields as $field) {

            if($request->hasFile($field) && $request->file($field)) {

                $files[$field] = $request->file($field);

            }

        }

        return $files;

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
