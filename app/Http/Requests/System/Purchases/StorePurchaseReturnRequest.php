<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Purchases;

use App\Http\Requests\System\Base\{CompanyFormRequest};
use App\Rules\System\Defaults\{ExistsInTenant};

final class StorePurchaseReturnRequest extends CompanyFormRequest {
    public function authorize(): bool {

        return parent::authorize();

    }

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "purchase_receipt_id" => ["nullable", "integer", new ExistsInTenant("purchase_receipts")],
            "warehouse_id" => ["required", "integer", new ExistsInTenant("warehouses", ["status" => "active"])],
            "returned_at" => ["nullable", "date"],
            "reason" => ["required", "string", "max:500"],
            "items" => ["required", "array", "min:1", "max:100"],
            "items.*.purchase_item_id" => ["required", "integer", "distinct"],
            "items.*.quantity" => ["required", "numeric", "gt:0", "max:{$maxValue}", "decimal:0,{$round}"],
        ];

    }
}
