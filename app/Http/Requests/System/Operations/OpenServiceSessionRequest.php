<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Operations;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class OpenServiceSessionRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "branch_id" => ["required", "integer"],
            "service_station_id" => ["nullable", "integer"],
            "customer_id" => ["nullable", "integer"],
            "assigned_user_id" => ["nullable", "integer"],
            "item_id" => ["nullable", "integer"],
            "quantity" => ["nullable", "numeric", "min:0.0001", "max:{$maxValue}", "decimal:0,{$round}"],
            "session_type" => ["required", "string", "max:30"],
            "start_immediately" => ["nullable", "boolean"],
            "started_at" => ["nullable", "date"],
            "scheduled_at" => ["nullable", "date"],
            "expected_end_at" => ["nullable", "date", "after:scheduled_at"],
            "tolerance_minutes" => ["nullable", "integer", "min:0", "max:1440"],
            "queue_code" => ["nullable", "string", "max:30"],
            "observation" => ["nullable", "string", "max:500"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["session_type", "queue_code", "observation"];

    }

}
