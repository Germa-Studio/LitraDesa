<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('EnsureUserIsAdmin middleware triggered', [
            'url' => $request->url(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'user_role' => $request->user()?->role,
            'is_admin' => $request->user()?->isAdmin(),
        ]);

        if (!$request->user() || !$request->user()->isAdmin()) {
            Log::warning('Admin middleware: Access denied', [
                'user_id' => $request->user()?->id,
                'user_role' => $request->user()?->role,
            ]);
            abort(403, 'Unauthorized action.');
        }

        Log::info('Admin middleware: Access granted');
        return $next($request);
    }
}

// Made with Bob
