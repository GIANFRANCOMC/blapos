<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\BookComplaints;

use App\Helpers\System\{Utilities};
use App\Models\System\Organizations\{BookComplaint, BookComplaintStatusHistory};
use Illuminate\Contracts\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing BookComplaint operations
 * Handles business logic for listing and updating book complaints
 */
class BookComplaintService {
    /**
     * Allowed fields for book complaint update
     */
    private const ALLOWED_UPDATE_FIELDS = [
        "admin_response",
        "public_response",
        "status",
    ];

    /**
     * Get paginated list of book complaints with filters
     *
     * @param  int  $companyId Company ID
     * @param  array  $filters Filter parameters
     * @param  int  $perPage Items per page
     */
    public static function getPaginatedList(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator {

        return BookComplaint::query()
            ->with(["branch", "identityDocumentType", "attachments", "statusHistories.changedBy", "respondedBy"])
            ->when(Utilities::isDefined($filters["status"] ?? null), function($query) use ($filters) {

                $query->where("status", $filters["status"]);

            })
            ->when(Utilities::isDefined($filters["type"] ?? null), function($query) use ($filters) {

                $query->where("type", $filters["type"]);

            })
            ->when(Utilities::isDefined($filters["branch_id"] ?? null), function($query) use ($filters) {

                $query->where("branch_id", $filters["branch_id"]);

            })
            ->when(Utilities::isDefined($filters["word"] ?? null), function($query) use ($filters) {

                $word = trim((string) $filters["word"]);
                $query->where(function($nested) use ($word) {

                    $nested->where("tracking_code", "like", "%{$word}%")
                        ->orWhere("document_number", "like", "%{$word}%")
                        ->orWhere("name", "like", "%{$word}%")
                        ->orWhere("email", "like", "%{$word}%");

                });

            })
            ->orderByDesc("id")
            ->paginate($perPage);

    }

    /**
     * Find book complaint by ID and company ID
     *
     * @param  int  $id Book complaint ID
     * @param  int  $companyId Company ID
     * @param  array  $relations Relations to eager load
     */
    public static function findByIdInTenant(int $id, int $companyId, ?array $relations = null): ?BookComplaint {

        return BookComplaint::query()
            ->with($relations ?? ["branch", "identityDocumentType", "attachments", "statusHistories.changedBy", "respondedBy"])
            ->find($id);

    }

    /**
     * Update an existing book complaint
     * Only allows updating internal/public responses and status
     *
     * @param  BookComplaint  $bookComplaint Book complaint instance to update
     * @param  array  $data Updated book complaint data
     * @param  int|null  $userId User ID updating the book complaint
     * @return BookComplaint Updated book complaint instance
     */
    public static function update(BookComplaint $bookComplaint, array $data, ?int $userId = null): BookComplaint {

        DB::transaction(function() use ($bookComplaint, $data, $userId) {

            $updateData = [];
            $previousStatus = (string) $bookComplaint->status;

            // Only allow updating specific fields

            foreach(self::ALLOWED_UPDATE_FIELDS as $field) {

                if(isset($data[$field])) {

                    $updateData[$field] = $data[$field];

                }

            }

            // Only update if there are changes

            if(!empty($updateData)) {

                if(($updateData["status"] ?? $previousStatus) === "resolved") {

                    $updateData["responded_at"] = now();
                    $updateData["responded_by"] = $userId;

                }elseif($previousStatus === "resolved") {

                    $updateData["responded_at"] = null;
                    $updateData["responded_by"] = null;

                }

                $updateData["updated_at"] = now();

                $updateData["updated_by"] = $userId;

                $bookComplaint->update($updateData);

                $newStatus = (string) $bookComplaint->status;

                if($newStatus !== $previousStatus) {

                    BookComplaintStatusHistory::create([
                        "book_complaint_id" => $bookComplaint->id,
                        "changed_by" => $userId,
                        "previous_status" => $previousStatus,
                        "new_status" => $newStatus,
                        "note" => $data["status_note"] ?? null,
                        "changed_at" => now(),
                    ]);

                }

            }

        });

        return $bookComplaint->fresh([
            "branch",
            "identityDocumentType",
            "attachments",
            "statusHistories.changedBy",
            "respondedBy",
        ]);

    }
}
