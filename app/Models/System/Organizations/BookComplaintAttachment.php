<?php

declare(strict_types=1);

namespace App\Models\System\Organizations;

use Illuminate\Database\Eloquent\{Model};

final class BookComplaintAttachment extends Model {
    protected $table = "book_complaint_attachments";

    public $timestamps = false;

    protected $fillable = [
        "book_complaint_id",
        "file_name",
        "file_path",
        "mime_type",
        "file_size",
        "created_at",
    ];

    protected $casts = ["file_size" => "integer", "created_at" => "datetime"];

    public function complaint() {

        return $this->belongsTo(BookComplaint::class, "book_complaint_id");

    }
}
