<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs\Products;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class ImportProductsRequest extends CompanyFormRequest {
    public function rules(): array {

        $maxFileSizeKb = $this->numericMaxFileSizeKb();

        return [
            "file" => ["required", "file", "mimes:xlsx,xls,csv", "max:{$maxFileSizeKb}"],
            "warehouse_id" => [
                "bail",
                "required",
                "integer",
                new ExistsInTenant("warehouses", ["status" => "active"], "El almacen seleccionado no esta disponible."),
            ],
        ];

    }

    public function messages(): array {

        $maxFileSizeMb = round($this->numericMaxFileSizeKb() / 1024, 2);

        return array_merge(parent::messages(), [
            "file.file" => "Seleccione un archivo valido.",
            "file.mimes" => "Use un archivo Excel o CSV.",
            "file.max" => "El archivo no debe superar {$maxFileSizeMb} MB.",
        ]);

    }
}
