<?php

declare(strict_types=1);

namespace App\Models\System\Sales;

use App\Models\System\Customers\{Customer};
use App\Models\System\General\{Currency};
use App\Models\System\Organizations\{Branch, User};
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany, Relations\HasOne};

final class QuotationHeader extends Model {
    protected $table = "quotation_headers";

    protected $fillable = [
        "branch_id",
        "holder_id",
        "seller_id",
        "currency_id",
        "sale_header_id",
        "reference",
        "issue_date",
        "valid_until",
        "subtotal",
        "tax",
        "total",
        "observation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "converted_at",
        "converted_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "issue_date" => "date:Y-m-d",
        "valid_until" => "date:Y-m-d",
        "subtotal" => "App\\Casts\\System\\ConfigurableDecimal",
        "tax" => "App\\Casts\\System\\ConfigurableDecimal",
        "total" => "App\\Casts\\System\\ConfigurableDecimal",
        "converted_at" => "datetime",
        "canceled_at" => "datetime",
    ];

    public function holder(): BelongsTo {

        return $this->belongsTo(Customer::class, "holder_id");

    }

    public function seller(): BelongsTo {

        return $this->belongsTo(User::class, "seller_id");

    }

    public function branch(): BelongsTo {

        return $this->belongsTo(Branch::class, "branch_id");

    }

    public function currency(): BelongsTo {

        return $this->belongsTo(Currency::class, "currency_id");

    }

    public function sale(): HasOne {

        return $this->hasOne(SaleHeader::class, "quotation_header_id");

    }

    public function items(): HasMany {

        return $this->hasMany(QuotationItem::class, "quotation_header_id");

    }

    public function taxes(): HasMany {

        return $this->hasMany(QuotationTax::class, "quotation_header_id")
            ->where("status", "active");

    }
}
