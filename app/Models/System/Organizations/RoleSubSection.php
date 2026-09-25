<?php

namespace App\Models\System\Organizations;

use App\Models\System\General\{SubSection};
use Illuminate\Database\Eloquent\{Model};

class RoleSubSection extends Model {
    protected $table = "role_sub_sections";

    protected $fillable = [
        "role_id",
        "sub_section_id",
        "actions",
        "status",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        "actions" => "array",
    ];

    public function role() {

        return $this->belongsTo(Role::class, "role_id", "id");

    }

    public function subSection() {

        return $this->belongsTo(SubSection::class, "sub_section_id", "id");

    }
}
