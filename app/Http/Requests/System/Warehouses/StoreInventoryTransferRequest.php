<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Warehouses;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreInventoryTransferRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "source_warehouse_id" => ["required", "integer", "different:destination_warehouse_id"],
            "destination_warehouse_id" => ["required", "integer"],
            "items" => ["required", "array", "min:1", "max:100"],
            "items.*.item_id" => ["required", "integer", "distinct"],
            "items.*.quantity" => ["required", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
            "reason" => ["required", "string", "max:255"],
        ];

    }

    protected function normalizedStringFields(): array {

        return ["reason"];

    }

}
