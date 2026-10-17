<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Catalogs;

use App\Exports\System\Catalogs\Offerings\{OfferingImportTemplateExport, OfferingListExport};
use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Catalogs\Offerings\{ImportOfferingsRequest};
use App\Http\Requests\System\Catalogs\Services\{StoreServiceRequest, UpdateServiceRequest};
use App\Imports\System\Catalogs\Offerings\{OfferingBasicImport};
use App\Models\System\Catalogs\{Item};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Catalogs\Services\{ServiceConfigService, ServiceService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Validation\{ValidationException};
use Maatwebsite\Excel\Facades\{Excel};
use Symfony\Component\HttpFoundation\{BinaryFileResponse};

class ServiceController extends BaseController {

    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Catalogs.service";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return ServiceConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return ServiceService::getPaginatedList($filters, $perPage);

    }

    public function export(Request $request): BinaryFileResponse {

        return Excel::download(
            new OfferingListExport(Item::TYPE_SERVICE, $this->getFilters($request)),
            "servicios_".now()->format("Y-m-d_His").".xlsx"
        );

    }

    public function importTemplate(): BinaryFileResponse {

        return Excel::download(
            new OfferingImportTemplateExport(Item::TYPE_SERVICE),
            "plantilla_servicios.xlsx"
        );

    }

    public function import(ImportOfferingsRequest $request): JsonResponse {

        try {

            $currencyId = (int) app(TenantCompanyContext::class)->get()->currency_id;
            $import = new OfferingBasicImport(Item::TYPE_SERVICE, $currencyId, $this->getUserId());

            Excel::import($import, $request->file("file"));

            InitParamsCacheInvalidationService::invalidate(InitParamsCacheInvalidationService::ITEMS);

            return response()->json([
                "bool" => true,
                "msg" => "{$import->importedCount()} servicios importados correctamente.",
                "data" => ["imported" => $import->importedCount()],
            ]);

        }catch(ValidationException $exception) {

            throw $exception;

        }catch(\Throwable $exception) {

            report($exception);

            return response()->json([
                "bool" => false,
                "msg" => "No se pudo completar la carga. Revisa la plantilla e intenta nuevamente.",
            ], 422);

        }

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Catalogs/services/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreServiceRequest $request): JsonResponse {

        try {

            $data = $this->prepareServiceData($request);
            $item = ServiceService::create($data, $this->getUserId());

            if(!Utilities::isDefined($item)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ITEMS
            );

            return $this->createdResponse($item, "created", "item");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Service ID
     */
    public function update(UpdateServiceRequest $request, int $id): JsonResponse {

        try {

            $item = ServiceService::findByIdInTenant($id, null);

            if(!Utilities::isDefined($item)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareServiceData($request);

            $item = ServiceService::update($item, $data, $this->getUserId());

            if(!Utilities::isDefined($item)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::ITEMS
            );

            return $this->updatedResponse($item, "updated", "item");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreServiceRequest|UpdateServiceRequest  $request
     */
    private function prepareServiceData($request): array {

        return [
            "internal_code" => $request->input("internal_code"),
            "name" => $request->input("name"),
            "description" => $request->input("description"),
            "price" => $request->input("price"),
            "price_includes_tax" => $request->boolean("price_includes_tax"),
            "igv_exempt" => $request->boolean("igv_exempt"),
            "min_price" => $request->input("min_price"),
            "max_price" => $request->input("max_price"),
            "currency_id" => $request->input("currency_id"),
            "estimated_duration_minutes" => $request->input("estimated_duration_minutes"),
            "commission_rate" => $request->input("commission_rate"),
            "commission_type" => $request->input("commission_type"),
            "commission_value" => $request->input("commission_value"),
            "capacity_control_enabled" => $request->boolean("capacity_control_enabled"),
            "capacity_limit" => $request->input("capacity_limit"),
            "expires_at" => $request->input("expires_at"),
            "see_my_web" => $request->input("see_my_web"),
            "see_my_web_price" => $request->input("see_my_web_price"),
            "status" => $request->input("status"),
            "categories" => $request->input("categories"),
        ];

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }

}
