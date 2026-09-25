<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use Illuminate\Database\Eloquent\Relations\{BelongsTo};
use Illuminate\Database\Eloquent\{Model};

final class PaymentMethodVariant extends Model {
    protected $table = "payment_method_variants";

    protected $fillable = [
        "payment_method_id",
        "code",
        "name",
        "sunat_code",
        "image_path",
        "description",
        "requires_reference",
        "is_default",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "requires_reference" => "boolean",
        "is_default" => "boolean",
    ];

    public function paymentMethod(): BelongsTo {

        return $this->belongsTo(PaymentMethod::class, "payment_method_id");

    }
}
