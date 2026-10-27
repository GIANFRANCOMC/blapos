<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\System\Organizations\Companies\CompanyLogomarkService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CompanyLogomarkServiceTest extends TestCase {

    public function test_it_uses_the_company_logomark_when_the_file_exists(): void {

        Storage::fake("public");
        $path = "tenants/tests/branding/logomark.png";
        Storage::disk("public")->put($path, "image");

        $this->assertSame(asset("storage/".$path), app(CompanyLogomarkService::class)->url($path));

    }

    public function test_it_uses_the_default_logomark_when_the_company_has_no_valid_file(): void {

        Storage::fake("public");
        $service = app(CompanyLogomarkService::class);
        $defaultUrl = asset(config("app.owner_app")->assets->img->logomark);

        $this->assertSame($defaultUrl, $service->url(null));
        $this->assertSame($defaultUrl, $service->url("  "));
        $this->assertSame($defaultUrl, $service->url("tenants/tests/branding/missing.png"));

    }

}
