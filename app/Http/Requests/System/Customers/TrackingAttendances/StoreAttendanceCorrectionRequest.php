<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\TrackingAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreAttendanceCorrectionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "start_date" => "nullable|date",
            "end_date" => "nullable|date|after:start_date",
            "reason" => "required|string|max:500",
        ];

    }

    protected function normalizedStringFields(): array {

        return ["start_date", "end_date", "reason"];

    }

}
