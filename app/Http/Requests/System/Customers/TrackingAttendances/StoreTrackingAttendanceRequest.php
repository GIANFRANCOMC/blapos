<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\TrackingAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class StoreTrackingAttendanceRequest extends CompanyFormRequest {
    public function rules(): array {

        return [
            "branch_id" => [
                "bail",
                "required",
                "integer",
                new ExistsInTenant("branches", ["status" => "active"], "La sucursal seleccionada no esta disponible."),
            ],
            "customer_id" => [
                "bail",
                "required",
                "integer",
                new ExistsInTenant("customers", ["status" => "active"], "El cliente seleccionado no esta disponible."),
            ],
            "start_date" => ["nullable", "date"],
            "end_date" => ["nullable", "date", "after:start_date"],
            "observation" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["start_date", "end_date", "observation"];

    }
}
