<?php

declare(strict_types=1);

namespace App\Models\System\Purchases;

use Illuminate\Database\Eloquent\{Model};

final class SupplierContact extends Model {
    protected $table = "supplier_contacts";

    protected $fillable = ["supplier_id", "name", "position", "telephone", "email", "is_primary", "status"];

    protected $casts = ["is_primary" => "boolean"];
}
