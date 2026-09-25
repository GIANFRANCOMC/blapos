<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\TrackingAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class ProcessTrackingAttendanceBatchRequest extends CompanyFormRequest {
    public function rules(): array {

        return [
            "branch_id" => [
                "bail",
                "required",
                "integer",
                new ExistsInTenant("branches", ["status" => "active"], "La sucursal seleccionada no esta disponible."),
            ],
            "customers" => ["required", "array", "min:1", "max:200"],
            "customers.*.customer_id" => ["nullable", "required_without:customers.*.customer_document_number", "integer"],
            "customers.*.customer_document_number" => ["nullable", "required_without:customers.*.customer_id", "string", "max:30"],
            "customers.*.customer_attendance_type" => ["nullable", "string", "in:carnet,document_number,dni,dnie"],
            "start_date" => ["nullable", "date"],
            "end_date" => ["nullable", "date", "after:start_date"],
            "observation" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["start_date", "end_date", "observation"];

    }
}
