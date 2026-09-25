<?php

declare(strict_types=1);

namespace App\Http\Requests\Guest;

use Illuminate\Foundation\Http\{FormRequest};
use Illuminate\Validation\{Rule};

final class PublicAttendanceRequest extends FormRequest {
    public function authorize(): bool {

        return true;

    }

    public function rules(): array {

        return [
            "branch_id" => [
                "required",
                "integer",
                Rule::exists("branches", "id")
                    ->where("status", "active"),
            ],
            "customers" => ["required", "array", "min:1", "max:20"],
            "customers.*.customer_id" => [
                "required",
                "integer",
                Rule::exists("customers", "id")
                    ->where("status", "active"),
            ],
        ];

    }
}
