<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Warehouses;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Support\System\Warehouses\{InventoryOperationTemplate};
use Illuminate\Validation\{Rule};

final class PreviewInventoryOperationRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "warehouse_id" => ["required", "integer"],
            "operation_code" => ["required", "string", Rule::in(InventoryOperationTemplate::codes())],
            "file" => ["required", "file", "mimes:xlsx,xls,csv", "max:".$this->numericMaxFileSizeKb()],
        ];

    }

    public function messages(): array {

        return array_merge(parent::messages(), [
            "file.mimes" => "Usa un archivo Excel o CSV.",
            "file.max" => "El archivo supera el tamaño permitido.",
        ]);

    }

}
