<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\{Builder};
use Illuminate\Database\Eloquent\{Model};

final class PurchasePayableInstallment extends Model {

    protected $table = "purchase_payable_installments";

    protected $fillable = [
        "purchase_account_payable_id",
        "installment_number",
        "due_date",
        "amount",
        "paid_amount",
        "pending_amount",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "due_date" => "date:Y-m-d",
        "amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "paid_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "pending_amount" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function scopeOutstanding(Builder $query): Builder {

        return $query->whereIn("status", ["pending", "partial", "overdue"]);

    }

    public function accountPayable(): BelongsTo {

        return $this->belongsTo(PurchaseAccountPayable::class, "purchase_account_payable_id");

    }
}
