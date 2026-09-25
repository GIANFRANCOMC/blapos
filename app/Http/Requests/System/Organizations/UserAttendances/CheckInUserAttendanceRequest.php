<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\UserAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};
use App\Services\System\Organizations\Users\{UserAttendanceService};
use Illuminate\Validation\{Rule};

final class CheckInUserAttendanceRequest extends CompanyFormRequest {
    public function rules(): array {

        return [
            "branch_id" => [
                "required",
                "integer",
                new ExistsInTenant("branches", ["status" => "active"], "La sucursal no está disponible."),
            ],
            "user_id" => [
                "required",
                "integer",
                new ExistsInTenant("users", ["status" => "active"], "El colaborador no está disponible."),
            ],
            "checked_in_at" => ["nullable", "date"],
            "source_type" => ["nullable", Rule::in(UserAttendanceService::sourceTypes())],
            "source_reference" => ["nullable", "string", "max:100"],
            "observation" => ["nullable", "string", "max:500"],
        ];

    }

    public function attributes(): array {

        return [
            "branch_id" => "sucursal",
            "user_id" => "colaborador",
            "checked_in_at" => "fecha y hora de ingreso",
            "source_type" => "origen del registro",
            "source_reference" => "referencia del origen",
            "observation" => "observación",
        ];

    }
}
