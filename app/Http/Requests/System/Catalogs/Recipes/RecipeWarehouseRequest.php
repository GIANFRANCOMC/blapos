<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Recipes;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class RecipeWarehouseRequest extends CompanyFormRequest {
    public function rules(): array {

        return [
            "warehouse_id" => [
                "bail",
                "required",
                "integer",
                new ExistsInTenant("warehouses", ["status" => "active"], "El almacen seleccionado no esta disponible."),
            ],
        ];

    }
}
