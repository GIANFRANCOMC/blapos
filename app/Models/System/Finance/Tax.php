<?php

declare(strict_types=1);

namespace App\Models\System\Finance;

use Illuminate\Database\Eloquent\{Model};

final class Tax extends Model {
    protected $table = "taxes";

    protected $fillable = [
        "code",
        "name",
        "description",
        "rate",
        "calculation_type",
        "operation_type",
        "min_apply_quantity",
        "max_apply_quantity",
        "scope",
        "is_required",
        "is_default",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "rate" => "App\\Casts\\System\\ConfigurableDecimal",
        "min_apply_quantity" => "integer",
        "max_apply_quantity" => "integer",
        "is_required" => "boolean",
        "is_default" => "boolean",
    ];
}
