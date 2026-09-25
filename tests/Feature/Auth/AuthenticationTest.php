<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\{EnsureAuthenticatedSession, EnsureTenantSession, ResolveTenant, TrustHosts};
use App\Models\System\Organizations\{User};
use App\Providers\{RouteServiceProvider};
use Illuminate\Foundation\Testing\{RefreshDatabase};
use Tests\Concerns\{ProvisionsSystemDatabase};
use Tests\{TestCase};

class AuthenticationTest extends TestCase {
    use ProvisionsSystemDatabase;
    use RefreshDatabase;

    protected function setUp(): void {

        parent::setUp();
        config(["public_access.captcha.enabled" => false]);
        $this->provisionSystemDatabase();
        $this->withoutMiddleware([
            TrustHosts::class,
            ResolveTenant::class,
            EnsureTenantSession::class,
            EnsureAuthenticatedSession::class,
        ]);

    }

    public function test_login_screen_can_be_rendered(): void {

        $response = $this->get("/login");

        $response->assertStatus(200);

    }

    public function test_users_can_authenticate_using_the_login_screen(): void {

        $user = User::where("email", "admin@example.test")->firstOrFail();

        $response = $this->post("/login", [
            "email" => $user->email,
            "password" => "password",
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

    }

    public function test_users_can_not_authenticate_with_invalid_password(): void {

        $user = User::where("email", "admin@example.test")->firstOrFail();

        $this->post("/login", [
            "email" => $user->email,
            "password" => "wrong-password",
        ]);

        $this->assertGuest();

    }

    public function test_users_can_logout(): void {

        $user = User::where("email", "admin@example.test")->firstOrFail();

        $response = $this->actingAs($user)->post("/logout");

        $this->assertGuest();
        $response->assertRedirect("/");

    }
}
