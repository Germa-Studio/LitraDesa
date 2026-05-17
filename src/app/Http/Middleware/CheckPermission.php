<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The permission name required
     * @param  string|null  $guard  The guard to use (optional)
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $guard = null): Response
    {
        if (!$request->user($guard)) {
            abort(401, 'Unauthenticated.');
        }

        if (!$request->user($guard)->hasPermission($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}

// Made with Bob
