<?php

declare(strict_types=1);

namespace App\Http\Controllers\System\Organizations;

use App\Helpers\System\{Utilities};
use App\Http\Controllers\System\Base\{BaseController};
use App\Http\Requests\System\Organizations\BookComplaints\{UpdateBookComplaintRequest};
use App\Models\System\Organizations\{BookComplaintAttachment};
use App\Services\System\Organizations\BookComplaints\{BookComplaintConfigService, BookComplaintService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Storage};
use Symfony\Component\HttpFoundation\{StreamedResponse};

class BookComplaintController extends BaseController {
    /**
     * Translation namespace for book complaint module
     */
    private const TRANSLATION_NAMESPACE = "System.Organizations.book_complaint";

    /**
     * Get initialization parameters for the module
     *
     * @return \stdClass
     */
    public function initParams(Request $request) {

        $page = $this->getPage($request);

        return BookComplaintConfigService::getInitParams($this->getCompanyId(), $page, $this->getUserId());

    }

    /**
     * Get paginated list of book complaints with filters
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(Request $request) {

        $filters = array_merge($this->getFilters($request), $request->only([
            "status",
            "type",
            "branch_id",
            "word",
        ]));

        $perPage = $this->getPerPage($request, Utilities::$per_page_default);

        return BookComplaintService::getPaginatedList($this->getCompanyId(), $filters, $perPage);

    }

    /**
     * Display the book complaints index page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index() {

        return view("System/general/Organizations/book_complaints/main");

    }

    /**
     * Update the specified book complaint
     *
     * @param  int  $id Book complaint ID
     */
    public function update(UpdateBookComplaintRequest $request, int $id): JsonResponse {

        try {

            $bookComplaint = BookComplaintService::findByIdInTenant($id, $this->getCompanyId(), null);

            if(!Utilities::isDefined($bookComplaint)) {

                return $this->notFoundResponse();

            }

            $data = $this->prepareBookComplaintData($request);

            $bookComplaint = BookComplaintService::update($bookComplaint, $data, $this->getUserId());

            if(!Utilities::isDefined($bookComplaint)) {

                return $this->errorResponse("update_failed");

            }

            return $this->updatedResponse($bookComplaint, "updated", "bookComplaint");

        }catch(\Exception $e) {

            return $this->handleException($e, "update");

        }

    }

    public function downloadAttachment(int $attachmentId): StreamedResponse {

        $attachment = BookComplaintAttachment::query()
            ->findOrFail($attachmentId);

        abort_unless(Storage::disk("local")->exists($attachment->file_path), 404);

        return Storage::disk("local")->download(
            $attachment->file_path,
            $attachment->file_name,
            ["Content-Type" => $attachment->mime_type]
        );

    }

    /**
     * Prepare book complaint data from request
     */
    private function prepareBookComplaintData(UpdateBookComplaintRequest $request): array {

        return [
            "admin_response" => $request->admin_response,
            "public_response" => $request->public_response,
            "status_note" => $request->status_note,
            "status" => $request->status,
        ];

    }

    /**
     * Get translation namespace for book complaint module
     */
    protected function getTranslationNamespace(): string {

        return self::TRANSLATION_NAMESPACE;

    }
}
