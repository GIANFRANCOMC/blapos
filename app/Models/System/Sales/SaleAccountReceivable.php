<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use App\Models\System\Customers\{Customer};
use App\Models\System\General\{Currency};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\{Builder, Model};

final class SaleAccountReceivable extends Model {
    protected $table = "sale_accounts_receivable";

    protected $fillable = [
        "sale_header_id",
        "customer_id",
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

    public function scopeOutstanding(Builder $query): Builder {

        return $query->whereIn("status", ["pending", "partial", "overdue"]);

    }

    public function scopeForCustomer(Builder $query, int $customerId): Builder {

        return $query->where("customer_id", $customerId);

    }

    public function sale(): BelongsTo {

        return $this->belongsTo(SaleHeader::class, "sale_header_id");

    }

    public function customer(): BelongsTo {

        return $this->belongsTo(Customer::class, "customer_id");

    }

    public function currency(): BelongsTo {

        return $this->belongsTo(Currency::class, "currency_id");

    }

    public function installments(): HasMany {

        return $this->hasMany(SaleReceivableInstallment::class, "sale_account_receivable_id");

    }

    public function payments(): HasMany {

        return $this->hasMany(SaleReceivablePayment::class, "sale_account_receivable_id")
            ->where("status", "active");

    }
}
