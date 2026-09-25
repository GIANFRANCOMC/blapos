<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use App\Models\System\General\{SubSection};
use Illuminate\Database\Eloquent\{Model};

final class UserNavigationMetric extends Model {
    protected $table = "user_navigation_metrics";

    public $timestamps = false;

    protected $fillable = [
        "user_id",
        "sub_section_id",
        "visit_count",
        "recent_rank",
    ];

    protected $casts = [
        "user_id" => "integer",
        "sub_section_id" => "integer",
        "visit_count" => "integer",
        "recent_rank" => "integer",
    ];

    public function user() {

        return $this->belongsTo(User::class, "user_id", "id");

    }

    public function subSection() {

        return $this->belongsTo(SubSection::class, "sub_section_id", "id");

    }
}
