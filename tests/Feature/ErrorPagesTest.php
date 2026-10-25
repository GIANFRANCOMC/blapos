<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ErrorPagesTest extends TestCase {

    public function test_common_error_pages_share_the_same_layout(): void {

        foreach([400, 401, 403, 404, 405, 419, 422, 429, 500, 503] as $code) {

            $html = view("errors.{$code}")->render();

            $this->assertStringContainsString("Error {$code}", $html);
            $this->assertStringContainsString('class="br-error-page"', $html);
            $this->assertStringContainsString("¿Necesitas ayuda?", $html);
            $this->assertStringContainsString('href="mailto:gianfranco_29_01@hotmail.com"', $html);
            $this->assertStringContainsString('href="tel:+51987057624"', $html);
            $action = match($code) {
                401, 419 => "Iniciar sesión",
                503 => "Reintentar",
                default => "Ir al inicio",
            };

            $this->assertStringContainsString($action, $html, "Error {$code}");

        }

    }

    public function test_existing_custom_messages_are_escaped(): void {

        $html = view("errors.404", ["msg" => "<script>alert(1)</script>"])->render();

        $this->assertStringContainsString("&lt;script&gt;alert(1)&lt;/script&gt;", $html);
        $this->assertStringNotContainsString("<script>alert(1)</script>", $html);

    }

    public function test_framework_keeps_the_403_status_with_the_custom_page(): void {

        config()->set("app.debug", false);

        $request = Request::create("/restricted", "GET", server: ["HTTP_ACCEPT" => "text/html"]);
        $response = app(ExceptionHandler::class)->render($request, new HttpException(403));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString("No tienes acceso", $response->getContent());

    }

    public function test_json_errors_do_not_render_the_html_page(): void {

        config()->set("app.debug", false);

        $request = Request::create("/restricted", "GET", server: ["HTTP_ACCEPT" => "application/json"]);
        $response = app(ExceptionHandler::class)->render($request, new HttpException(403));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertStringContainsString("application/json", (string) $response->headers->get("Content-Type"));
        $this->assertStringNotContainsString("br-error-page", $response->getContent());

    }

}
