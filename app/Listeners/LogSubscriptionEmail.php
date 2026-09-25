<?php

namespace App\Listeners;

use App\Events\{SubscriptionExpired};
use App\Helpers\System\{Utilities};
use App\Models\System\Customers\{Subscription, SubscriptionEmail};
use App\Services\System\Tenancy\{TenantCompanyContext};

class LogSubscriptionEmail {
    /**
     * Create the event listener.
     */
    public function __construct() {

        //

    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionExpired $event) {

        $subscription = $event->subscription;
        $branch = $subscription->branch;
        $customer = $subscription->customer;
        $ownerApp = Utilities::getOwnerApp();

        $company = app(TenantCompanyContext::class)->get();
        $socialsMedia = $company->socialsMedia;

        $touchpoints = collect();

        $whatsapp = $socialsMedia->where("type", "whatsapp");

        if(count($whatsapp) === 1) {

            $touchpoints->push($whatsapp->first());

        }

        $extrasJson = [
            "customer" => [
                "id" => $customer->id,
                "identity_document_type_id" => $customer->identity_document_type_id,
                "document_number" => $customer->document_number,
                "name" => $customer->name,
                "email" => $customer->email,
                "phone_number" => $customer->phone_number,
            ],
        ];

        SubscriptionEmail::create([
            "to" => $customer->email ?? "",
            "subject" => "Tu membresía ha expirado",
            "body" => view("emails.subscriptions.expired.default", compact("subscription", "branch", "company", "customer", "ownerApp", "touchpoints"))->render(),
            "extras_json" => json_encode($extrasJson),
            "type" => "SubscriptionExpired",
            "model_id" => $subscription->id,
            "model_type" => Subscription::class,
            "status" => "pending",
            "created_at" => now(),
        ]);

    }
}
