<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use Illuminate\Support\Facades\Storage;

final class CompanyLogomarkService {

    public function url(?string $path): string {

        $path = trim($path ?? "");

        if($path !== "" && Storage::disk("public")->exists($path)) {

            return asset("storage/".$path);

        }

        return asset(config("app.owner_app")->assets->img->logomark);

    }

}
