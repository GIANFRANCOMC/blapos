<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use Illuminate\Database\Eloquent\{Model};

final class PurchaseExpense extends Model {
    protected $table = "purchase_expenses";

    protected $fillable = ["purchase_header_id", "expense_type", "name", "amount", "allocation_method", "note"];

    protected $casts = ["amount" => "App\\Casts\\System\\ConfigurableDecimal"];
}
