<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use App\Models\System\Finance\{Tax};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class QuotationTax extends Model {
    protected $table = "quotation_taxes";

    protected $fillable = [
        "quotation_header_id",
        "tax_id",
        "name",
        "description",
        "rate",
        "calculation_type",
        "operation_type",
        "is_required",
        "quantity",
        "base_amount",
        "amount",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "rate" => "App\\Casts\\System\\ConfigurableDecimal",
        "is_required" => "boolean",
        "quantity" => "integer",
        "base_amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "amount" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function quotation(): BelongsTo {

        return $this->belongsTo(QuotationHeader::class, "quotation_header_id");

    }

    public function tax(): BelongsTo {

        return $this->belongsTo(Tax::class, "tax_id");

    }
}
