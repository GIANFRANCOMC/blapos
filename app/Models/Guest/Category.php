<?php

declare(strict_types=1);

namespace App\Models\Guest;

use Illuminate\Database\Eloquent\{Model};

final class Category extends Model {
    protected $table = "categories";

    protected $hidden = [
        "created_by", "updated_by", "created_at", "updated_at", "pivot",
    ];

    protected $casts = [
        "sort_order" => "integer",
        "is_public" => "boolean",
    ];
}
