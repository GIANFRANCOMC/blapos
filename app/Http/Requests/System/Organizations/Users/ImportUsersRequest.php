<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Organizations\Users;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class ImportUsersRequest extends CompanyFormRequest {

    public function rules(): array {

        return [
            "file" => ["required", "file", "mimes:xlsx,xls,csv", "max:".$this->numericMaxFileSizeKb()],
        ];

    }

    public function messages(): array {

        return array_merge(parent::messages(), [
            "file.file" => "Selecciona un archivo válido.",
            "file.mimes" => "Usa un archivo Excel o CSV.",
            "file.max" => "El archivo supera el tamaño permitido.",
        ]);

    }

}
