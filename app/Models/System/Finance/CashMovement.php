<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use App\Models\System\Organizations\{Branch, User};
use Illuminate\Database\Eloquent\{Model};

final class CashMovement extends Model {
    protected $table = "cash_movements";

    protected $fillable = [
        "branch_id",
        "cash_session_id",
        "payment_method_id",
        "user_id",
        "movement_type",
        "origin_type",
        "origin_id",
        "amount",
        "reference",
        "note",
        "occurred_at",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function cashSession() {

        return $this->belongsTo(CashSession::class, "cash_session_id", "id");

    }

    public function paymentMethod() {

        return $this->belongsTo(PaymentMethod::class, "payment_method_id", "id");

    }

    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }
}
