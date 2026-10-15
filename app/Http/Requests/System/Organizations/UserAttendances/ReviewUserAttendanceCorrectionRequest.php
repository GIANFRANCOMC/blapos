<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\UserAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class ReviewUserAttendanceCorrectionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "approve" => ["required", "boolean"],
            "note" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["note"];

    }

}
