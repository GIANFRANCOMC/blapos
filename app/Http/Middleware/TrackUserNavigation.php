<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\System\Essentials\{UserNavigationService};
use Closure;
use Illuminate\Http\{Request};
use Symfony\Component\HttpFoundation\{Response};
use Throwable;

final class TrackUserNavigation {

    public function __construct(private readonly UserNavigationService $navigationService) {

    }

    public function handle(Request $request, Closure $next): Response {

        $routeName = $request->route()?->getName();
        $requestedPath = "/".ltrim($request->path(), "/");
        $response = $next($request);
        $user = $request->user();

        if(
            !$user
            || !$request->isMethod("GET")
            || $request->ajax()
            || $request->expectsJson()
            || !$routeName
            || $response->getStatusCode() >= 400
        ) {

            return $response;

        }

        try {

            $this->navigationService->record($user, $routeName, $requestedPath);

        }catch(Throwable $exception) {

            report($exception);

        }

        return $response;

    }

}
