<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  One or more role names required (user needs at least one)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            abort(401, 'Unauthenticated.');
        }

        if (!$request->user()->hasAnyRole($roles)) {
            abort(403, 'Anda tidak memiliki peran yang diperlukan untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}

// Made with Bob
