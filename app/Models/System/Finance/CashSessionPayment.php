<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use Illuminate\Database\Eloquent\{Model};

final class CashSessionPayment extends Model {
    protected $table = "cash_session_payments";

    protected $fillable = [
        "cash_session_id",
        "payment_method_id",
        "payment_method_name",
        "expected_amount",
        "counted_amount",
        "difference_amount",
        "note",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function cashSession() {

        return $this->belongsTo(CashSession::class, "cash_session_id", "id");

    }

    public function paymentMethod() {

        return $this->belongsTo(PaymentMethod::class, "payment_method_id", "id");

    }
}
