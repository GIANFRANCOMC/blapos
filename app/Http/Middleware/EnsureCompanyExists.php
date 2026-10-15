<?php

namespace App\Http\Middleware;

use App\Helpers\System\{Utilities};
use App\Models\Guest\{Company};
use Closure;
use Illuminate\Http\{Request};

class EnsureCompanyExists {

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next) {

        $slug = $request->route("company_slug");

        if(!Utilities::isDefined($slug)) {

            abort(404, "Invalid company");

        }

        $company = Company::where("slug", $slug)
            ->where("status", "active")
            ->first();

        if(!Utilities::isDefined($company)) {

            abort(404, "Company not found");

        }

        // Add in controller
        $request->attributes->add(["company" => $company]);

        return $next($request);

    }

}
