<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\{Builder, Model};

final class SaleReceivableInstallment extends Model {

    protected $table = "sale_receivable_installments";

    protected $fillable = [
        "sale_account_receivable_id",
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

    public function accountReceivable(): BelongsTo {

        return $this->belongsTo(SaleAccountReceivable::class, "sale_account_receivable_id");

    }
}
