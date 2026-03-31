<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow API requests to pass through (only check web routes)
        if ($request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }

        // Check if user is authenticated
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if password_changed_at is null
        if ($user->password_changed_at === null) {
            // Allow if already on the password force-change route
            if ($request->routeIs('password.force-change')) {
                return $next($request);
            }

            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
