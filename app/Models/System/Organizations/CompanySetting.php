<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use Illuminate\Database\Eloquent\{Model};

final class CompanySetting extends Model {

    protected $table = "company_settings";

    public $timestamps = true;

    protected $fillable = [
        "company_id",
        "group",
        "key",
        "value",
        "description",
        "value_type",
        "status",
        "created_by",
        "updated_by",
    ];

    public function company() {

        return $this->belongsTo(Company::class, "company_id", "id");

    }

}
