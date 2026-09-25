<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use Illuminate\Database\Eloquent\{Model};

final class BookComplaintStatusHistory extends Model {
    protected $table = "book_complaint_status_histories";

    public $timestamps = false;

    protected $fillable = [
        "book_complaint_id",
        "changed_by",
        "previous_status",
        "new_status",
        "note",
        "changed_at",
    ];

    protected $casts = ["changed_at" => "datetime"];

    public function complaint() {

        return $this->belongsTo(BookComplaint::class, "book_complaint_id");

    }

    public function changedBy() {

        return $this->belongsTo(User::class, "changed_by");

    }
}
