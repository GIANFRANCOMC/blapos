<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Essentials;

use App\Models\System\Sales\{SaleBody};
use Illuminate\Validation\{Rule};

final class CommercialPerformanceRequest extends AnalyticsPeriodRequest {

    public function rules(): array {

        return parent::rules() + [
            "type" => ["nullable", Rule::in(array_column(SaleBody::getTypes(), "code"))],
            "include_evolution" => ["sometimes", "boolean"],
        ];

    }

    public function attributes(): array {

        return parent::attributes() + ["type" => "Tipo de ítem"];

    }

}
