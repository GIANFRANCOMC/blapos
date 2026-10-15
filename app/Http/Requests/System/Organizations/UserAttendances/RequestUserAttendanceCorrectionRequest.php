<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\UserAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class RequestUserAttendanceCorrectionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "checked_in_at" => ["nullable", "date", "required_without:checked_out_at"],
            "checked_out_at" => ["nullable", "date", "after:checked_in_at", "required_without:checked_in_at"],
            "reason" => ["required", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["checked_in_at", "checked_out_at", "reason"];

    }

}
