<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use App\Models\System\Finance\{PaymentMethod, PaymentMethodVariant};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class PurchasePayment extends Model {

    protected $table = "purchase_payments";

    protected $fillable = [
        "purchase_header_id",
        "payment_method_id",
        "payment_method_variant_id",
        "name",
        "amount",
        "reference",
        "note",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "amount" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function purchase(): BelongsTo {

        return $this->belongsTo(PurchaseHeader::class, "purchase_header_id");

    }

    public function paymentMethod(): BelongsTo {

        return $this->belongsTo(PaymentMethod::class, "payment_method_id");

    }

    public function paymentMethodVariant(): BelongsTo {

        return $this->belongsTo(PaymentMethodVariant::class, "payment_method_variant_id");

    }
}
