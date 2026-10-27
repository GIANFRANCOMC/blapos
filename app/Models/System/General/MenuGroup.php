<?php

namespace App\Models\System\General;

use Illuminate\Database\Eloquent\{Model};

final class MenuGroup extends Model {

    protected $fillable = ["section_id", "slug", "name", "dom_icon", "order", "status"];

    public function section() {

        return $this->belongsTo(Section::class);

    }

    public function subSections() {

        return $this->hasMany(SubSection::class)->where("status", "active");

    }

}
