<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    /**
     * Handle an incoming request.
     *
     * This middleware should run after the auth middleware.
     * It checks if the session has an 'impersonating_id' and swaps
     * the current user with the impersonated user.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only proceed if user is authenticated
        if (! Auth::check()) {
            return $next($request);
        }

        // Check if session has 'impersonating_id'
        $impersonatingId = Session::get('impersonating_id');

        if ($impersonatingId === null) {
            return $next($request);
        }

        // Store original user ID in session if not already stored
        if (! Session::has('original_user_id')) {
            Session::put('original_user_id', Auth::id());
        }

        // Swap the current user with the impersonated user
        $impersonatedUser = Auth::getProvider()->retrieveById($impersonatingId);

        if ($impersonatedUser !== null) {
            Auth::setUser($impersonatedUser);
        }

        return $next($request);
    }
}
