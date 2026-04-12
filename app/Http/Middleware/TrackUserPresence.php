<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\UserStatusService;
use App\Services\UserActivityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class TrackUserPresence
{
    public function __construct(
        private readonly UserStatusService $statusService,
        private readonly UserActivityService $activityService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Update heartbeat in Redis (prevents auto-away)
        $this->statusService->updateHeartbeat($user);

        // Log activity to MySQL (throttled to 1 per minute per user)
        $this->activityService->logPageView($user, $request->path());

        return $next($request);
    }
}
