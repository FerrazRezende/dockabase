<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Pennant\Feature;

class EnsureFeatureIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        $user = $request->user();

        // Admin users bypass feature flags
        if ($user instanceof User && $user->hasRole('admin')) {
            return $next($request);
        }

        // Check if feature is active
        if (!Feature::active($feature)) {
            if ($request->wantsJson()) {
                return new JsonResponse([
                    'error' => 'feature_disabled',
                    'message' => 'This feature is not available for your account',
                ], Response::HTTP_FORBIDDEN);
            }

            abort(Response::HTTP_FORBIDDEN, 'This feature is not available for your account');
        }

        return $next($request);
    }
}
