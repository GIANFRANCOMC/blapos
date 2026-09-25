<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Helpers\System\{Utilities};
use App\Models\System\Customers\{SubscriptionEmail};
use App\Services\System\Organizations\{BusinessAuditService};
use DomainException;

/**
 * Service class for managing Tracking Notification operations
 * Handles business logic for listing and managing tracking notifications
 */
class TrackingNotificationService {
    /**
     * Get paginated list of subscription emails with filters
     *
     * @param  int  $companyId Company ID
     * @param  array  $filters Filters array
     * @param  int  $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getPaginatedList(int $companyId, array $filters, int $perPage) {

        $status = $filters["status"] ?? null;

        return SubscriptionEmail::when(Utilities::isDefined($status), function($query) use ($status) {

            $query->where(function($query) use ($status) {

                $query->where("status", $status);

            });

        })
            ->orderBy("id", "DESC")
            ->paginate($perPage);

    }

    public static function retry(int $companyId, int $userId, int $notificationId): SubscriptionEmail {

        $notification = SubscriptionEmail::query()
            ->findOrFail($notificationId);

        if($notification->status !== "failed") {

            throw new DomainException("Solo se pueden reintentar notificaciones fallidas.");

        }

        $before = $notification->getAttributes();

        $notification->forceFill([
            "status" => "pending",
            "attempts" => 0,
            "next_attempt_at" => now(),
            "failed_at" => null,
            "last_error" => null,
            "updated_by" => $userId,
        ])->save();

        BusinessAuditService::record(
            "tracking_notifications",
            "retry",
            "Notificación #{$notification->id} habilitada para reintento.",
            $notification,
            $before,
            $notification->getAttributes(),
            [],
            null,
            $userId
        );

        return $notification->fresh();

    }
}
