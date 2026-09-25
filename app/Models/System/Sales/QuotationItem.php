<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use App\Models\System\Catalogs\{Item};
use App\Models\System\General\{Currency};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};

final class QuotationItem extends Model {

    protected $table = "quotation_items";

    protected $fillable = [
        "quotation_header_id",
        "item_id",
        "currency_id",
        "name",
        "type",
        "quantity",
        "price",
        "price_includes_tax",
        "igv_exempt",
        "total",
        "observation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "quantity" => "App\\Casts\\System\\ConfigurableDecimal",
        "price" => "App\\Casts\\System\\ConfigurableDecimal",
        "price_includes_tax" => "boolean",
        "igv_exempt" => "boolean",
        "total" => "App\\Casts\\System\\ConfigurableDecimal",
    ];

    public function quotation(): BelongsTo {

        return $this->belongsTo(QuotationHeader::class, "quotation_header_id");

    }

    public function item(): BelongsTo {

        return $this->belongsTo(Item::class, "item_id");

    }

    public function currency(): BelongsTo {

        return $this->belongsTo(Currency::class, "currency_id");

    }
}
