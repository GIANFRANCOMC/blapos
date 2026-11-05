<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class SecurityHeadersTest extends TestCase {

    public function test_authenticated_json_and_html_cannot_be_reused_as_browser_pages(): void {

        Auth::shouldReceive("check")->twice()->andReturn(true);

        foreach(["application/json", "text/html; charset=UTF-8"] as $contentType) {

            $request = Request::create("/workspace", "GET");
            $request->setLaravelSession(new Store("test", new ArraySessionHandler(30)));

            $response = new Response("content", 200, [
                "Content-Type" => $contentType,
                "Vary" => "Origin",
            ]);

            $result = (new SecurityHeaders())->handle($request, fn() => $response);

            $this->assertSame("no-store, private", $result->headers->get("Cache-Control"));
            $this->assertSame("no-cache", $result->headers->get("Pragma"));
            $this->assertContains("Origin", $result->getVary());
            $this->assertContains("Accept", $result->getVary());
            $this->assertContains("X-Requested-With", $result->getVary());

        }

    }

    public function test_non_negotiated_responses_keep_their_cache_policy(): void {

        $request = Request::create("/download", "GET");
        $response = new Response("file", 200, [
            "Content-Type" => "application/octet-stream",
            "Cache-Control" => "private, max-age=60",
        ]);

        $result = (new SecurityHeaders())->handle($request, fn() => $response);

        $this->assertSame("max-age=60, private", $result->headers->get("Cache-Control"));
        $this->assertSame([], $result->getVary());

    }

}
