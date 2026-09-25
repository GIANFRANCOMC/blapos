<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use App\Models\System\Finance\{PaymentMethod, PaymentMethodVariant};
use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\{Model};

final class SaleReceivablePayment extends Model {

    protected $table = "sale_receivable_payments";

    protected $fillable = [
        "sale_account_receivable_id",
        "payment_method_id",
        "payment_method_variant_id",
        "paid_at",
        "amount",
        "reference",
        "observation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "paid_at" => "datetime",
        "amount" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function accountReceivable(): BelongsTo {

        return $this->belongsTo(SaleAccountReceivable::class, "sale_account_receivable_id");

    }

    public function paymentMethod(): BelongsTo {

        return $this->belongsTo(PaymentMethod::class, "payment_method_id");

    }

    public function paymentMethodVariant(): BelongsTo {

        return $this->belongsTo(PaymentMethodVariant::class, "payment_method_variant_id");

    }
}
