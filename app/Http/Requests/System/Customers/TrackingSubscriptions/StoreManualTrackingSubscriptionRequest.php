<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\TrackingSubscriptions;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class StoreManualTrackingSubscriptionRequest extends CompanyFormRequest {
    public function rules(): array {

        return [
            "branch_id" => ["required", "integer", new ExistsInTenant("branches", ["status" => "active"], "La sucursal seleccionada no pertenece a la empresa.")],
            "customer_id" => ["required", "integer", new ExistsInTenant("customers", ["status" => "active"], "El cliente seleccionado no pertenece a la empresa.")],
            "item_id" => ["nullable", "integer", new ExistsInTenant("items", ["type" => "subscription", "status" => "active"], "La membresía seleccionada no pertenece al catálogo activo.")],
            "duration_type" => ["nullable", "in:hour,day,today,month,year"],
            "duration_value" => ["nullable", "integer", "min:1", "max:9999"],
            "start_date" => ["required", "date"],
            "end_date" => ["required", "date", "after_or_equal:start_date"],
            "set_end_of_day" => ["nullable", "boolean"],
            "force" => ["nullable", "boolean"],
            "attendance_limit_per_day" => ["nullable", "integer", "min:1", "max:999"],
            "observation" => ["nullable", "string", "max:500"],
            "send_welcome_email" => ["nullable", "boolean"],
        ];

    }
}
