<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Organizations\Branches\{StoreBranchRequest, UpdateBranchRequest};
use App\Services\System\Base\{InitParamsCacheInvalidationService};
use App\Services\System\Organizations\Branches\{BranchConfigService, BranchService, SerieService};
use App\Services\System\Tenancy\{TenantCompanyContext};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{URL};

class BranchController extends BaseController {
    /**
     * Translation namespace for module
     */
    private const TRANSLATION_NAMESPACE = "System.Organizations.branch";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return BranchConfigService::getInitParams($page, $this->getUserId());

    }

    /**
     * Get paginated list with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return BranchService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Display the module index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Organizations/branches/main");

    }

    /**
     * Store a newly created record
     */
    public function store(StoreBranchRequest $request): JsonResponse {

        try {

            $data = $this->prepareBranchData($request);
            $branch = BranchService::create($data, $this->getCompanyId(), $this->getUserId());

            if(!Utilities::isDefined($branch)) {

                return $this->errorResponse("create_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BRANCHES,
                $this->getCompanyId()
            );

            return $this->createdResponse($branch, "created", "branch");

        }catch(\Exception $e) {

            return $this->handleException($e, "create");

        }

    }

    /**
     * Update the specified record
     *
     * @param  int  $id Branch ID
     */
    public function update(UpdateBranchRequest $request, int $id): JsonResponse {

        try {

            $branch = BranchService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($branch)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareBranchData($request);

            $branch = BranchService::update($branch, $data, $this->getUserId());

            if(!Utilities::isDefined($branch)) {

                return $this->errorResponse("update_failed");

            }

            InitParamsCacheInvalidationService::invalidate(
                InitParamsCacheInvalidationService::BRANCHES,
                $this->getCompanyId()
            );

            return $this->updatedResponse($branch, "updated", "branch");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    public function seriesAudit(Request $request): JsonResponse {

        $filters = $request->only([
            "branch_id", "serie_id", "user_id", "source", "action", "date_from", "date_to",
        ]);

        return response()->json([
            "bool" => true,
            "data" => SerieService::auditQuery($this->getCompanyId(), $filters)
                ->paginate($this->getPerPage($request, Utilities::$per_page_default)),
            "gaps" => SerieService::detectGaps(
                $this->getCompanyId(),
                $request->filled("branch_id") ? (int) $request->branch_id : null
            ),
        ]);

    }

    public function exportSeriesAudit(Request $request) {

        $rows = SerieService::auditQuery($this->getCompanyId(), $request->only([
            "branch_id", "serie_id", "user_id", "source", "action", "date_from", "date_to",
        ]))->get();

        return response()->streamDownload(function() use ($rows) {

            $output = fopen("php://output", "w");
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ["Fecha", "Sucursal", "Serie", "Correlativo", "Acción", "Origen", "Responsable"], ";");

            foreach($rows as $row) {

                fputcsv($output, [
                    $row->occurred_at,
                    $row->branch_name,
                    "{$row->serie_code}{$row->serie_number}",
                    $row->sequential,
                    $row->action,
                    $row->source,
                    $row->user_name,
                ], ";");

            }

            fclose($output);

        }, "auditoria-correlativos-".now()->format("Ymd-His").".csv", [
            "Content-Type" => "text/csv; charset=UTF-8",
        ]);

    }

    public function publicAttendanceLink(Request $request, int $id): JsonResponse {

        $branch = BranchService::findByIdInTenant($id, $this->getCompanyId(), ["active"]);

        if(!$branch) {

            return $this->notFoundResponse();

        }

        $minutes = max(5, min((int) $request->input("expires_in_minutes", 1440), 10080));

        $expiresAt = now()->addMinutes($minutes);
        $url = URL::temporarySignedRoute(
            "guest.tracking_attendances.signed",
            $expiresAt,
            [
                "company_slug" => app(TenantCompanyContext::class)->get()->slug,
                "branch" => $branch->id,
            ]
        );

        return response()->json([
            "bool" => true,
            "data" => [
                "url" => $url,
                "expires_at" => $expiresAt->toIso8601String(),
            ],
        ]);

    }

    /**
     * Prepare record data from request
     *
     * @param  StoreBranchRequest|UpdateBranchRequest  $request
     */
    private function prepareBranchData($request): array {

        return [
            "internal_code" => $request->input("internal_code"),
            "name" => $request->input("name"),
            "address" => $request->input("address"),
            "reference" => $request->input("reference"),
            "telephone" => $request->input("telephone"),
            "email" => $request->input("email"),
            "capacity" => $request->input("capacity"),
            "map_url" => $request->input("map_url"),
            "status" => $request->input("status"),
        ];

    }

    /**
     * Get translation namespace for module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
