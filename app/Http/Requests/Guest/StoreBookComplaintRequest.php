<?php

declare(strict_types=1);

namespace App\Http\Requests\Guest;

use App\Services\Security\{TurnstileVerificationService};
use App\Services\System\Organizations\Companies\{CompanySettingService};
use Illuminate\Foundation\Http\{FormRequest};
use Illuminate\Validation\{Rule, Validator};

final class StoreBookComplaintRequest extends FormRequest {
    public function authorize(): bool {

        return true;

    }

    public function rules(): array {

        $companyId = (int) $this->attributes->get("company")?->id;
        $maxFileSizeKb = $this->numericMaxFileSizeKb($companyId);

        return [
            "branch_id" => [
                "required",
                "integer",
                Rule::exists("branches", "id")->where("status", "active"),
            ],
            "identity_document_type_id" => [
                "required",
                "integer",
                Rule::exists("identity_document_types", "id"),
            ],
            "document_number" => ["required", "string", "max:30"],
            "name" => ["required", "string", "max:255"],
            "email" => ["nullable", "email:rfc", "max:255", "required_without:phone_number"],
            "phone_number" => ["nullable", "string", "max:30", "required_without:email"],
            "type" => ["required", Rule::in(["complaint", "claim", "suggestion"])],
            "description" => ["required", "string", "max:5000"],
            "request" => ["nullable", "string", "max:5000"],
            "evidence" => ["nullable", "string", "max:500"],
            "attachments" => ["nullable", "array", "max:5"],
            "attachments.*" => ["file", "mimes:pdf,jpg,jpeg,png", "max:{$maxFileSizeKb}"],
            "cf-turnstile-response" => [
                Rule::requiredIf(TurnstileVerificationService::enabled()),
                "nullable",
                "string",
                "max:2048",
            ],
            "website" => ["prohibited"],
        ];

    }

    private function numericMaxFileSizeKb(int $companyId): int {

        $settings = CompanySettingService::group(
            CompanySettingService::NUMERIC_VALIDATION
        );

        return max(1, (int) ($settings["max_file_size_kb"] ?? 4096));

    }

    public function after(): array {

        return [function(Validator $validator): void {

            if($validator->errors()->has("cf-turnstile-response")) {

                return;

            }

            if(!TurnstileVerificationService::verify(
                $this->input("cf-turnstile-response"),
                $this->ip()
            )) {

                $validator->errors()->add("captcha", "No se pudo validar el control antiabuso.");

            }

        }];

    }
}
