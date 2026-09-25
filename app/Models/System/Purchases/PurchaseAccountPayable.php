<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Models\System\General\{Currency};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\{Model};

final class PurchaseAccountPayable extends Model {

    protected $table = "purchase_accounts_payable";

    protected $fillable = [
        "purchase_header_id",
        "supplier_id",
        "currency_id",
        "issue_date",
        "due_date",
        "payment_modality",
        "original_amount",
        "extra_percentage",
        "extra_amount",
        "total_amount",
        "paid_amount",
        "pending_amount",
        "status",
        "observation",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "issue_date" => "date:Y-m-d",
        "due_date" => "date:Y-m-d",
        "original_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "extra_percentage" => "App\\Casts\\System\\ConfigurableDecimal",
        "extra_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "total_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "paid_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "pending_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "canceled_at" => "datetime",
    ];

    public function purchase(): BelongsTo {

        return $this->belongsTo(PurchaseHeader::class, "purchase_header_id");

    }

    public function supplier(): BelongsTo {

        return $this->belongsTo(Supplier::class, "supplier_id");

    }

    public function currency(): BelongsTo {

        return $this->belongsTo(Currency::class, "currency_id");

    }

    public function installments(): HasMany {

        return $this->hasMany(PurchasePayableInstallment::class, "purchase_account_payable_id");

    }

    public function payments(): HasMany {

        return $this->hasMany(PurchasePayablePayment::class, "purchase_account_payable_id")
            ->where("status", "active");

    }
}
