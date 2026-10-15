<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\BookComplaints;

use Illuminate\Foundation\Http\{FormRequest};

class UpdateBookComplaintRequest extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {

        return true;

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {

        return [
            "admin_response" => "nullable|string|max:2000|required_if:status,resolved",
            "public_response" => "nullable|string|max:2000|required_if:status,resolved",
            "status_note" => "nullable|string|max:500",
            "status" => "required|string|in:pending,in_progress,resolved",
        ];

    }

}
