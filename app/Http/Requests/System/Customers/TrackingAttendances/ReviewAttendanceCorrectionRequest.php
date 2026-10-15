<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\TrackingAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class ReviewAttendanceCorrectionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "decision" => "required|string|in:approved,rejected",
            "note" => "nullable|string|max:500",
        ];

    }

    protected function normalizedStringFields(): array {

        return ["decision", "note"];

    }

}
