<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Devices\BiometricDevices;

use App\Rules\System\Defaults\{ExistsInTenant};
use Illuminate\Foundation\Http\{FormRequest};

class UpdateBiometricDeviceRequest extends FormRequest {
    public function authorize(): bool {

        return true;

    }

    public function rules(): array {

        return [
            "branch_id" => ["required", "integer", new ExistsInTenant("branches", [], null)],
            "biometric_device_model_id" => ["required_without:model", "nullable", "integer", new ExistsInTenant("biometric_device_models", [], null)],
            "model" => "required_without:biometric_device_model_id|nullable|string|max:255",
            "brand" => "nullable|string|max:255",
            "name" => "required|string|max:50",
            "description" => "nullable|string|max:100",
            "serial_number" => "nullable|string|max:50",
            "ip_address" => "required|ip",
            "port" => "nullable|integer|min:1|max:65535",
            "device_id" => "nullable|string|max:50",
            "status" => "required|in:active,inactive",
        ];

    }
}
