<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\{Request};
use Symfony\Component\HttpFoundation\{Response};

final class EnsurePublicAttendanceAccess {
    public function handle(Request $request, Closure $next): Response {

        $access = $request->session()->get("_public_attendance_access");
        $branchId = (int) $request->input("branch_id");

        if(!is_array($access)
            || (int) ($access["branch_id"] ?? 0) !== $branchId
            || (int) ($access["expires_at"] ?? 0) < now()->timestamp) {

            abort(403, "El enlace de asistencia no es válido o ya venció.");

        }

        return $next($request);

    }
}
