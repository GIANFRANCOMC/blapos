<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use Illuminate\Database\Eloquent\{Model};

final class MiscExpenseCategory extends Model {
    protected $table = "misc_expense_categories";

    protected $fillable = [
        "name",
        "description",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
}
