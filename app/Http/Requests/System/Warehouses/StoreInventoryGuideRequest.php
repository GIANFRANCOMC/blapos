<?php

declare(strict_types=1);

namespace App\Http\Requests\System\Warehouses;

use App\Http\Requests\System\Base\{CompanyFormRequest};

final class StoreInventoryGuideRequest extends CompanyFormRequest {

    public function rules(): array {

        $round = $this->decimalPrecision();
        $maxValue = $this->numericMaxValue();

        return [
            "warehouse_id" => "required|integer",
            "guide_type" => "required|in:entry,exit",
            "issue_date" => "required|date",
            "reason" => "required|string|max:255",
            "reference" => "nullable|string|max:100",
            "items" => "required|array|min:1|max:100",
            "items.*.item_id" => "required|integer|distinct",
            "items.*.quantity" => "required|numeric|gt:0|max:{$maxValue}|decimal:0,{$round}",
            "items.*.unit_cost" => "nullable|numeric|min:0|max:{$maxValue}|decimal:0,{$round}",
        ];

    }

}
