<?php

declare(strict_types=1);

namespace App\Models\System\Tenancy;

use Illuminate\Database\Eloquent\{Model};

final class PlatformUser extends Model {

    protected $connection = "landlord";

    protected $table = "platform_users";

    protected $fillable = [
        "name", "email", "password", "status", "session_version", "last_login_at", "last_login_ip",
    ];

    protected $hidden = ["password"];

    protected $casts = ["session_version" => "integer", "last_login_at" => "datetime"];

}
