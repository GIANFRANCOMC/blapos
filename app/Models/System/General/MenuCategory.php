<?php

namespace App\Models\System\General;

use Illuminate\Database\Eloquent\{Model};

final class MenuCategory extends Model {

    protected $fillable = ["slug", "name", "order", "status"];

    public function sections() {

        return $this->hasMany(Section::class)->where("status", "active");

    }

}
