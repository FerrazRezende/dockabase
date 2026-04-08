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
     * It checks if the session has an 'impersonate_target_id' and swaps
     * the current user with the impersonated user.
     *
     * Session keys:
     * - impersonate_target_id: ID of the user being impersonated
     * - impersonate_original_id: ID of the original admin user
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only proceed if user is authenticated
        if (! Auth::check()) {
            return $next($request);
        }

        // Check if session has 'impersonate_target_id'
        $impersonateTargetId = Session::get('impersonate_target_id');

        if ($impersonateTargetId === null) {
            return $next($request);
        }

        // Store original user ID in session if not already stored
        if (! Session::has('impersonate_original_id')) {
            Session::put('impersonate_original_id', Auth::id());
        }

        // Swap the current user with the impersonated user
        $impersonatedUser = Auth::getProvider()->retrieveById($impersonateTargetId);

        if ($impersonatedUser !== null) {
            Auth::setUser($impersonatedUser);
        }

        return $next($request);
    }
}
