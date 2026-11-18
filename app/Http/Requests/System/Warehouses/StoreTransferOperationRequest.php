<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Warehouses;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreTransferOperationRequest extends CompanyFormRequest {
    public function rules(): array {
        return [
            "request_key" => ["required", "uuid"],
            "expected_version" => ["required", "integer", "min:0"],
            "kind" => ["nullable", "in:return,loss"],
            "reason" => ["required_with:kind", "nullable", "string", "max:255"],
            "items" => ["required", "array", "min:1", "max:100"],
            "items.*.id" => ["required", "integer", "min:1", "distinct"],
            "items.*.quantity" => ["required", ...$this->numericRules(min: 0), "gt:0"],
        ];
    }
    protected function normalizedStringFields(): array { return ["reason"]; }
}
