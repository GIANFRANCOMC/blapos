<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use Illuminate\Database\Eloquent\Relations\{HasMany};
use Illuminate\Database\Eloquent\{Model};

final class PaymentMethod extends Model {
    protected $table = "payment_methods";

    protected $fillable = [
        "code",
        "name",
        "category",
        "sunat_code",
        "description",
        "image_path",
        "scope",
        "requires_reference",
        "supports_variants",
        "allows_partial_payment",
        "is_default",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "requires_reference" => "boolean",
        "supports_variants" => "boolean",
        "allows_partial_payment" => "boolean",
        "is_default" => "boolean",
    ];

    public function variants(): HasMany {

        return $this->hasMany(PaymentMethodVariant::class, "payment_method_id")
            ->where("status", "active");

    }
}
