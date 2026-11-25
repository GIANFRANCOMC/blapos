<?php

declare(strict_types=1);

namespace App\Services\System\Organizations\Companies;

use App\Services\System\Tenancy\{TenantContext, TenantStoragePath};
use Illuminate\Support\Facades\{Storage};

final class CompanyBrandingService {

    /** @return array{url: string, fallback: string} */
    public function resolve(string $resource, ?string $path, string $routeName): array {
        $fallback = $this->defaultUrl($resource);

        return [
            "url" => $this->configuredPath($resource, $path) === null ? $fallback : route($routeName, [], false),
            "fallback" => $fallback,
        ];
    }

    public function defaultUrl(string $resource): string {
        $path = config("app.owner_app")->assets->img->{$resource};
        $url = asset($path);
        $publicPath = public_path($path);

        return is_file($publicPath) ? $url."?v=".filemtime($publicPath) : $url;
    }

    public function configuredPath(string $resource, ?string $path): ?string {
        $path = trim($path ?? "");
        if($path === "" || !app(TenantContext::class)->active()) {
            return null;
        }

        $directory = TenantStoragePath::branding()."/";
        if(!str_starts_with($path, $directory)) {
            return null;
        }

        $fileName = substr($path, strlen($directory));
        if(!preg_match('/\A'.preg_quote($resource, '/').'\.(?i:png|jpe?g)\z/', $fileName)) {
            return null;
        }

        return Storage::disk("public")->fileExists($path) ? $path : null;
    }

}
