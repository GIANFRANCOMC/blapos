<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Customers\Customers;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class RegisterCustomerFingerprintRequest extends CompanyFormRequest {
    public function rules(): array {

        return [
            "biometric_device_id" => [
                "bail",
                "required",
                "integer",
                new ExistsInTenant(
                    "biometric_devices",
                    ["status" => "active"],
                    "El dispositivo biométrico no está disponible."
                ),
            ],
            "device_user_id" => ["nullable", "integer", "min:1"],
            "finger_index" => ["nullable", "integer", "min:0", "max:9"],
        ];

    }
}
