<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\TrackingSubscriptions;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class RenewTrackingSubscriptionRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "start_date" => "required|date",
            "end_date" => "required|date|after:start_date",
            "attendance_limit_per_day" => "nullable|integer|min:1|max:100",
            "observation" => "nullable|string|max:1000",
            "force" => "nullable|boolean",
        ];

    }

    protected function normalizedStringFields(): array {

        return ["start_date", "end_date", "observation"];

    }

}
