<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Essentials\Account;

use App\Rules\System\Defaults\{UniqueInTenant};
use Illuminate\Foundation\Http\{FormRequest};

final class UpdateAccountRequest extends FormRequest {
    protected function prepareForValidation(): void {

        $this->merge([
            "name" => trim((string) $this->input("name")),
            "email" => strtolower(trim((string) $this->input("email"))),
            "phone_number" => $this->filled("phone_number")
                ? trim((string) $this->input("phone_number"))
                : null,
        ]);

    }

    public function authorize(): bool {

        return $this->user() !== null;

    }

    public function rules(): array {

        return [
            "name" => ["required", "string", "max:100"],
            "email" => [
                "required",
                "email",
                "max:100",
                new UniqueInTenant("users", "email", (int) $this->user()->id, [], "correo electrónico"),
            ],
            "phone_number" => ["nullable", "string", "max:15"],
            "gender" => ["nullable", "in:male,female,other"],
            "birthdate" => ["nullable", "date", "before_or_equal:today"],
        ];

    }

    public function attributes(): array {

        return [
            "name" => "nombre completo",
            "email" => "correo electrónico",
            "phone_number" => "teléfono",
            "gender" => "género",
            "birthdate" => "fecha de nacimiento",
        ];

    }
}
