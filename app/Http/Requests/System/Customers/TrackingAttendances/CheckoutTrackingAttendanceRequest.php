<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\TrackingAttendances;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class CheckoutTrackingAttendanceRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "end_date" => ["nullable", "date"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["end_date"];

    }

}
