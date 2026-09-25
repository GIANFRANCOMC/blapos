<?php

declare(strict_types=1);

namespace App\Services\System\Notifications;

use App\Helpers\System\{Utilities};
use App\Models\System\Customers\{SubscriptionEmail};
use Exception;
use Illuminate\Support\Facades\{Mail};

/**
 * Service class for managing Notification operations
 * Handles business logic for sending subscription emails
 */
class NotificationService {
    /**
     * Send pending subscription emails
     *
     * @return array{processed:int, sent:int, failed:int}
     */
    public static function sendSubscriptionEmails(int $limit = 100): array {

        $summary = ["processed" => 0, "sent" => 0, "failed" => 0];

        $records = SubscriptionEmail::query()
            ->whereIn("status", ["pending", "failed"])
            ->whereColumn("attempts", "<", "max_attempts")
            ->where(function($query) {

                $query->whereNull("next_attempt_at")
                    ->orWhere("next_attempt_at", "<=", now());

            })

            ->orderBy("id")
            ->limit(max(1, min($limit, 500)))
            ->get();

        foreach($records as $record) {

            $summary["processed"]++;
            $record->increment("attempts");

            try {

                if(Utilities::isDefined($record->to)) {

                    Mail::html($record->body, function($message) use ($record) {

                        $message->to($record->to)
                            ->subject($record->subject);

                    });

                    $record->status = "sent";
                    $record->sent_at = now();
                    $record->failed_at = null;
                    $record->last_error = null;
                    $record->next_attempt_at = null;
                    $record->updated_at = now();
                    $record->updated_by = null;
                    $record->save();
                    $summary["sent"]++;

                }else {

                    throw new Exception("La notificación no tiene un destinatario válido.");

                }

            }catch(Exception $e) {

                $record->status = "failed";
                $record->failed_at = now();
                $record->last_error = mb_substr($e->getMessage(), 0, 500);
                $record->next_attempt_at = (int) $record->attempts < (int) $record->max_attempts
                    ? now()->addMinutes(min(60, 2 ** (int) $record->attempts))
                    : null;

                $record->updated_at = now();
                $record->updated_by = null;
                $record->save();
                $summary["failed"]++;

            }

        }

        return $summary;

    }
}
