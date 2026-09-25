<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use App\Models\System\Organizations\{Branch};
use Illuminate\Database\Eloquent\{Model};

final class CashRegister extends Model {
    protected $table = "cash_registers";

    protected $fillable = [
        "branch_id",
        "code",
        "name",
        "is_main",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "is_main" => "boolean",
    ];

    public function branch() {

        return $this->belongsTo(Branch::class, "branch_id", "id");

    }

    public function sessions() {

        return $this->hasMany(CashSession::class, "cash_register_id", "id");

    }

    public function openSession() {

        return $this->hasOne(CashSession::class, "cash_register_id", "id")
            ->where("status", "open")
            ->latest("opened_at");

    }
}
