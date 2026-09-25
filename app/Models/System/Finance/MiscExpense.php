<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use App\Models\System\General\{Currency};
use App\Models\System\Organizations\{Branch, User};
use Illuminate\Database\Eloquent\{Model};

final class MiscExpense extends Model {
    protected $table = "misc_expenses";

    protected $fillable = [
        "branch_id",
        "cash_session_id",
        "payment_method_id",
        "currency_id",
        "misc_expense_category_id",
        "responsible_user_id",
        "expense_date",
        "reference",
        "concept",
        "amount",
        "description",
        "observation",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "canceled_at",
        "canceled_by",
    ];

    protected $casts = [
        "expense_date" => "date:Y-m-d",
        "amount" => "App\\Casts\\System\\ConfigurableDecimal",
        "canceled_at" => "datetime",
    ];

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id");

    }

    public function cashSession() {

        return $this->belongsTo(CashSession::class, "cash_session_id");

    }

    public function paymentMethod() {

        return $this->belongsTo(PaymentMethod::class, "payment_method_id");

    }

    public function currency() {

        return $this->belongsTo(Currency::class, "currency_id");

    }

    public function category() {

        return $this->belongsTo(MiscExpenseCategory::class, "misc_expense_category_id");

    }

    public function responsibleUser() {

        return $this->belongsTo(User::class, "responsible_user_id");

    }
}
