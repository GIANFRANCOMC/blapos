<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use Illuminate\Database\Eloquent\{Model};

final class SupplierBankAccount extends Model {
    protected $table = "supplier_bank_accounts";

    protected $fillable = ["supplier_id", "bank_name", "currency_code", "account_number", "interbank_code", "is_primary", "status"];

    protected $casts = ["is_primary" => "boolean"];
}
