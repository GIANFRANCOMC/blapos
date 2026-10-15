<?php

declare(strict_types=1);

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\{FormRequest};
use Illuminate\Validation\{Rule};

final class BiometricDeviceEventRequest extends FormRequest {

    public function authorize(): bool {

        return true;

    }

    public function rules(): array {

        return [
            "event_uuid" => ["required", "string", "max:64"],
            "event_type" => ["required", Rule::in(["check_in", "check_out", "attendance"])],
            "subject_type" => ["required", Rule::in(["customer", "user"])],
            "device_user_id" => ["required", "integer", "min:1"],
            "occurred_at" => ["required", "date"],
            "payload" => ["nullable", "array"],
        ];

    }

}
