<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\Roles;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};
use Illuminate\Validation\{Rule};

class StoreRoleRequest extends CompanyFormRequest {
    protected function prepareForValidation(): void {

        $this->merge([
            "branch_scope_mode" => $this->input("branch_scope_mode", "all"),
            "cash_register_scope_mode" => $this->input("cash_register_scope_mode", "all"),
            "warehouse_scope_mode" => $this->input("warehouse_scope_mode", "all"),
        ]);

    }

    public function rules(): array {

        $roleId = (int) $this->route("id");
        $actionCodes = collect(config("permissions.actions", []))->pluck("code")->all();

        return [
            "name" => [
                "required",
                "string",
                "max:80",
                Rule::unique("roles", "name")
                    ->ignore($roleId),
            ],
            "is_full_access" => ["required", "boolean"],
            "sub_section_ids" => ["array"],
            "sub_section_ids.*" => ["integer", "distinct"],
            "permissions" => ["nullable", "array"],
            "permissions.*.sub_section_id" => ["required", "integer", "distinct"],
            "permissions.*.actions" => ["required", "array", "min:1"],
            "permissions.*.actions.*" => ["required", "string", Rule::in($actionCodes)],
            "branch_scope_mode" => ["required", Rule::in(["all", "restricted"])],
            "cash_register_scope_mode" => ["required", Rule::in(["all", "restricted"])],
            "warehouse_scope_mode" => ["required", Rule::in(["all", "restricted"])],
            "branch_ids" => ["required_if:branch_scope_mode,restricted", "array", "min:1"],
            "branch_ids.*" => ["integer", "distinct", new ExistsInTenant("branches")],
            "cash_register_ids" => ["required_if:cash_register_scope_mode,restricted", "array", "min:1"],
            "cash_register_ids.*" => ["integer", "distinct", new ExistsInTenant("cash_registers")],
            "warehouse_ids" => ["required_if:warehouse_scope_mode,restricted", "array", "min:1"],
            "warehouse_ids.*" => ["integer", "distinct", new ExistsInTenant("warehouses")],
            "status" => ["required", Rule::in(["active", "inactive"])],
        ];

    }

    public function messages(): array {

        return [
            "required" => "Campo obligatorio.",
            "unique" => "Ya existe un perfil con este nombre.",
            "boolean" => "Selecciona una opción válida.",
            "array" => "Selecciona opciones válidas.",
            "integer" => "Selecciona opciones válidas.",
            "distinct" => "No repitas una opción.",
            "in" => "Selecciona una opción válida.",
            "min" => "Selecciona al menos una acción.",
            "max" => "Supera la longitud permitida.",
        ];

    }
}
