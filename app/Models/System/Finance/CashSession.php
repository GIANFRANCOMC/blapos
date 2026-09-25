<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use App\Models\System\Organizations\{Branch, User};
use App\Models\System\Sales\{SaleHeader};
use Illuminate\Database\Eloquent\{Model};

final class CashSession extends Model {
    protected $table = "cash_sessions";

    protected $fillable = [
        "branch_id",
        "cash_register_id",
        "opened_by",
        "closed_by",
        "opened_at",
        "closed_at",
        "opening_amount",
        "expected_amount",
        "counted_amount",
        "difference_amount",
        "observation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function register() {

        return $this->belongsTo(CashRegister::class, "cash_register_id", "id");

    }

    public function openedBy() {

        return $this->belongsTo(User::class, "opened_by", "id");

    }

    public function closedBy() {

        return $this->belongsTo(User::class, "closed_by", "id");

    }

    public function movements() {

        return $this->hasMany(CashMovement::class, "cash_session_id", "id");

    }

    public function paymentSummary() {

        return $this->hasMany(CashSessionPayment::class, "cash_session_id", "id");

    }

    public function inventoryCounts() {

        return $this->hasMany(CashSessionInventoryCount::class, "cash_session_id", "id");

    }

    public function sales() {

        return $this->hasMany(SaleHeader::class, "cash_session_id", "id");

    }
}
