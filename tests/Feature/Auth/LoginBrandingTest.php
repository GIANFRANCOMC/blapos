<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Organizations\{User};
use App\Models\System\Tenancy\{TenantDatabase};
use App\Services\System\Organizations\Companies\{CompanyService};
use App\Services\System\Tenancy\{TenantCompanyContext, TenantContext, TenantStoragePath};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Illuminate\Http\{UploadedFile};
use Illuminate\Support\Facades\{Storage};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

final class LoginBrandingTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->provisionSystemDatabase();
        $this->withoutMiddleware([
            TrustHosts::class,
            ResolveTenant::class,
            EnsureTenantSession::class,
            EnsureAuthenticatedSession::class,
        ]);
        app(TenantContext::class)->set(new TenantDatabase([
            "slug" => "login-branding",
            "public_id" => "018fd259-23a8-74c0-b63d-05b0e856cf3d",
            "database_name" => "blapos_testing",
        ]));
        Storage::fake("public");
    }

    private const ASSETS = [
        "login_image" => ["loginImage", "login.image", "login_image.png"],
        "combinationmark" => ["combinationMark", "login.brand", "combinationmark.png"],
    ];

    public function test_login_displays_both_configured_images_through_their_tenant_routes(): void {

        $cover = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jW8sAAAAASUVORK5CYII=", true);
        $brand = file_get_contents(public_path("System/assets/img/utils/owner_app/combinationmark.png"));
        $company = CompanyService::update(
            app(TenantCompanyContext::class)->get(),
            [],
            [
                "login_image" => UploadedFile::fake()->createWithContent("fondo.png", $cover),
                "combinationmark" => UploadedFile::fake()->createWithContent("marca.png", $brand),
            ],
            (int) User::where("email", "admin@example.test")->firstOrFail()->getKey()
        );

        $page = $this->get("/login")->assertOk();
        $content = ["login_image" => $cover, "combinationmark" => $brand];

        foreach(self::ASSETS as $field => [$viewField, $routeName, $fileName]) {
            $this->assertSame(TenantStoragePath::branding($fileName), $company->{$field});
            $url = route($routeName, [], false);
            $fallback = $this->defaultAssetUrl($fileName);

            $page->assertViewHas("data", fn($data) => $data->{$viewField}["url"] === $url
                && $data->{$viewField}["fallback"] === $fallback)
                ->assertSee($url, false);

            $this->get($url)->assertOk()
                ->assertHeader("Content-Type", "image/png")
                ->assertHeader("X-Content-Type-Options", "nosniff")
                ->assertStreamedContent($content[$field]);
        }

    }

    public function test_each_asset_falls_back_independently_when_its_file_is_unavailable_or_outside_the_tenant(): void {

        $company = app(TenantCompanyContext::class)->get();
        $disk = Storage::disk("public");

        foreach(self::ASSETS as $field => [$viewField, $routeName, $fileName]) {
            $otherField = $field === "login_image" ? "combinationmark" : "login_image";
            [$otherViewField, $otherRouteName, $otherFileName] = self::ASSETS[$otherField];
            $otherPath = TenantStoragePath::branding($otherFileName);
            $foreignPath = "tenants/other-company/branding/{$fileName}";
            $fallback = $this->defaultAssetUrl($fileName);
            $url = route($routeName, [], false);
            $otherUrl = route($otherRouteName, [], false);

            $disk->put($otherPath, "recurso de la empresa");
            $disk->put(TenantStoragePath::branding($fileName), "recurso local");
            $disk->put($foreignPath, "recurso de otra empresa");

            foreach([null, TenantStoragePath::branding("{$field}.jpg"), $foreignPath, TenantStoragePath::branding()."/../branding/{$fileName}"] as $path) {
                $company->update([$field => $path, $otherField => $otherPath]);

                $this->get("/login")->assertOk()
                    ->assertViewHas("data", fn($data) => $data->{$viewField}["url"] === $fallback
                        && $data->{$viewField}["fallback"] === $fallback
                        && $data->{$otherViewField}["url"] === $otherUrl)
                    ->assertSee($fallback, false);

                $this->get($url)->assertRedirect($fallback);
            }
        }

    }

    private function defaultAssetUrl(string $fileName): string {
        $path = "System/assets/img/utils/owner_app/{$fileName}";

        return asset($path)."?v=".filemtime(public_path($path));
    }

    public function test_image_routes_require_a_tenant_context(): void {

        app(TenantContext::class)->set(null);

        foreach(self::ASSETS as [, $routeName]) {
            $this->get(route($routeName, [], false))->assertNotFound();
        }
    }

}
