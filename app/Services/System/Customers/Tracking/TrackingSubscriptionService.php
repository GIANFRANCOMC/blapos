<?php

declare(strict_types=1);

namespace App\Services\System\Customers\Tracking;

use App\Helpers\System\{TranslationHelper, Utilities};
use App\Models\System\Catalogs\{Item};
use App\Models\System\Customers\{Customer, Subscription, SubscriptionEmail};
use App\Models\System\Organizations\{Branch};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use DomainException;
use Illuminate\Pagination\{LengthAwarePaginator};
use Illuminate\Support\Facades\{DB};

/**
 * Service class for managing Tracking Subscription operations
 * Handles business logic for listing and managing subscriptions
 */
class TrackingSubscriptionService {
    /**
     * Translation namespace for tracking subscription module
     */
    private const TRANSLATION_NAMESPACE = "System.Customers.tracking_subscription";

    /**
     * Get translation with fallback
     *
     * @param  string  $key Translation key
     * @param  array  $replace Replacements
     */
    private static function trans(string $key, array $replace = []): string {

        return TranslationHelper::getWithFallback(self::TRANSLATION_NAMESPACE, $key, $replace);

    }

    /**
     * Get paginated list of subscriptions
     *
     * @param  array  $filters Filter parameters
     * @param  int  $perPage Items per page
     */
    public static function getPaginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator {

        $branch = Branch::where("id", $filters["branch_id"] ?? null)
            ->first();

        if(!Utilities::isDefined($branch)) {

            return new LengthAwarePaginator([], 0, 1, 1, ["path" => ""]);

        }

        $query = Subscription::query()
            ->where("branch_id", $branch->id);

        // Apply filters
        self::applyFilters($query, $filters);

        // Apply ordering

        $query->orderBy("id", "DESC")
            ->with(["branch", "saleHeader", "customer"]);

        return $query->paginate($perPage);

    }

    /**
     * Apply filters to query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    private static function applyFilters($query, array $filters): void {

        if(Utilities::isDefined($filters["customer_id"])) {

            $query->where("customer_id", $filters["customer_id"]);

        }

        if(Utilities::isDefined($filters["start_date"])) {

            $query->where("start_date", ">=", $filters["start_date"]." 00:00:00");

        }

        if(Utilities::isDefined($filters["end_date"])) {

            $query->where("end_date", "<=", $filters["end_date"]." 23:59:59");

        }

        if(Utilities::isDefined($filters["status"])) {

            $query->where("status", $filters["status"]);

        }

    }

    /**
     * Cancel a subscription
     *
     * @param  Subscription  $subscription Subscription instance
     * @param  string|null  $motive Cancellation motive
     * @param  int|null  $userId User ID
     *
     * @throws \Exception
     */
    public static function cancel(Subscription $subscription, ?string $motive = null, ?int $userId = null): Subscription {

        if(!in_array($subscription->status, ["active"])) {

            throw new \Exception("La membresía no puede ser anulada.");

        }

        $subscription->motive = $motive ?? "N/A";

        $subscription->status = "canceled";
        $subscription->updated_at = now();
        $subscription->updated_by = $userId;
        $subscription->canceled_at = now();
        $subscription->canceled_by = $userId;
        $subscription->save();

        return $subscription;

    }

    public static function assertDatesAvailable(
        int $branchId,
        int $customerId,
        string $startDate,
        string $endDate,
        bool $force = false,
        ?int $ignoreId = null
    ): void {

        if($force) {

            return;

        }

        $policy = (string) CompanySettingService::value(
            CompanySettingService::SUBSCRIPTIONS,
            "overlap_policy",
            "block"
        );

        if($policy === "allow") {

            return;

        }

        $overlap = Subscription::query()
            ->where("branch_id", $branchId)
            ->where("customer_id", $customerId)
            ->where("status", "active")
            ->when($ignoreId, fn($query) => $query->where("id", "!=", $ignoreId))
            ->where("start_date", "<=", $endDate)
            ->where("end_date", ">=", $startDate)
            ->exists();

        if($overlap) {

            throw new DomainException("El cliente ya tiene una membresía vigente que se superpone en esta sucursal.");

        }

    }

    public static function renew(Subscription $source, array $data, ?int $userId = null): Subscription {

        return DB::transaction(function() use ($source, $data, $userId) {

            self::assertDatesAvailable(
                (int) $source->branch_id,
                (int) $source->customer_id,
                (string) $data["start_date"],
                (string) $data["end_date"],
                (bool) ($data["force"] ?? false)
            );

            return Subscription::create([
                "branch_id" => $source->branch_id,
                "sale_header_id" => null,
                "sale_body_id" => null,
                "renewed_from_id" => $source->id,
                "customer_id" => $source->customer_id,
                "duration_type" => $source->duration_type,
                "duration_value" => $source->duration_value,
                "start_date" => $data["start_date"],
                "end_date" => $data["end_date"],
                "set_end_of_day" => $source->set_end_of_day,
                "force" => (bool) ($data["force"] ?? false),
                "attendance_limit_per_day" => $data["attendance_limit_per_day"]
                    ?? $source->attendance_limit_per_day,
                "observation" => $data["observation"] ?? null,
                "type" => "manual",
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

        });

    }

    public static function createManual(array $data, ?int $userId = null): Subscription {

        return DB::transaction(function() use ($data, $userId) {

            $customer = Customer::query()
                ->where("status", "active")
                ->findOrFail((int) $data["customer_id"]);

            $catalogSubscription = null;

            if(!empty($data["item_id"])) {

                $catalogSubscription = Item::query()
                    ->where("type", "subscription")
                    ->where("status", "active")
                    ->findOrFail((int) $data["item_id"]);

            }

            self::assertDatesAvailable(
                (int) $data["branch_id"],
                (int) $customer->id,
                (string) $data["start_date"],
                (string) $data["end_date"],
                (bool) ($data["force"] ?? false)
            );

            $subscription = Subscription::create([
                "branch_id" => (int) $data["branch_id"],
                "sale_header_id" => null,
                "sale_body_id" => null,
                "renewed_from_id" => null,
                "customer_id" => (int) $customer->id,
                "duration_type" => $data["duration_type"] ?? $catalogSubscription?->duration_type,
                "duration_value" => $data["duration_value"] ?? $catalogSubscription?->duration_value,
                "start_date" => $data["start_date"],
                "end_date" => $data["end_date"],
                "set_end_of_day" => (bool) ($data["set_end_of_day"] ?? false),
                "force" => (bool) ($data["force"] ?? false),
                "attendance_limit_per_day" => $data["attendance_limit_per_day"] ?? 1,
                "observation" => $data["observation"] ?? null,
                "type" => "manual",
                "status" => "active",
                "created_at" => now(),
                "created_by" => $userId,
            ]);

            if((bool) ($data["send_welcome_email"] ?? true)) {

                self::queueWelcomeEmail($subscription, $customer, $catalogSubscription, $userId);

            }

            return $subscription->fresh(["branch", "customer"]);

        });

    }

    public static function queueWelcomeEmail(
        Subscription $subscription,
        Customer $customer,
        ?Item $catalogSubscription = null,
        ?int $userId = null
    ): void {

        if(!Utilities::isDefined($customer->email)) {

            return;

        }

        $membershipName = $catalogSubscription?->name ?? "tu membresía";

        $body = view()->exists("emails.subscriptions.welcome.default")
            ? view("emails.subscriptions.welcome.default", compact("subscription", "customer", "catalogSubscription", "membershipName"))->render()
            : "<p>Hola {$customer->name},</p><p>Gracias por suscribirte a {$membershipName}. Tu membresía está activa desde {$subscription->start_date} hasta {$subscription->end_date}.</p>";

        SubscriptionEmail::create([
            "to" => $customer->email,
            "subject" => "Gracias por tu suscripción",
            "body" => $body,
            "extras_json" => json_encode([
                "customer" => [
                    "id" => $customer->id,
                    "name" => $customer->name,
                    "email" => $customer->email,
                ],
                "subscription" => [
                    "id" => $subscription->id,
                    "start_date" => $subscription->start_date,
                    "end_date" => $subscription->end_date,
                ],
                "catalog_item_id" => $catalogSubscription?->id,
            ]),
            "type" => "SubscriptionWelcome",
            "model_id" => $subscription->id,
            "model_type" => Subscription::class,
            "status" => "pending",
            "created_at" => now(),
            "created_by" => $userId,
        ]);

    }
}
