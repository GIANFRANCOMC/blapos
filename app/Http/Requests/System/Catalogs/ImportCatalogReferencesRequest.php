<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Catalogs;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class ImportCatalogReferencesRequest extends CompanyFormRequest {
    public function rules(): array {

        $maxFileSizeKb = $this->numericMaxFileSizeKb();

        return [
            "file" => ["required", "file", "mimes:xlsx,xls,csv", "max:{$maxFileSizeKb}"],
        ];

    }

    public function messages(): array {

        $maxFileSizeMb = round($this->numericMaxFileSizeKb() / 1024, 2);

        return array_merge(parent::messages(), [
            "file.file" => "Selecciona un archivo válido.",
            "file.mimes" => "Usa un archivo Excel o CSV.",
            "file.max" => "El archivo no debe superar {$maxFileSizeMb} MB.",
        ]);

    }
}
