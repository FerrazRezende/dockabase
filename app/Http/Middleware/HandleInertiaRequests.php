<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $activeFeatures = [];

        if ($user) {
            $featureService = app(FeatureFlagService::class);
            $activeFeatures = $featureService->getActiveFeaturesForUser($user);
        }

        // Build impersonation data
        $impersonating = [
            'is_impersonating' => Session::has('impersonating_id'),
            'original_user_id' => Session::get('original_user_id'),
        ];

        // If impersonating, fetch both original and target user details
        if ($impersonating['is_impersonating']) {
            $originalUserId = Session::get('original_user_id');
            $originalUser = User::find($originalUserId);

            $impersonating['original_user'] = $originalUser ? [
                'name' => $originalUser->name,
                'email' => $originalUser->email,
            ] : null;

            $impersonating['target_user'] = [
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'activeFeatures' => $activeFeatures,
            'impersonating' => $impersonating,
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'messageType' => fn () => $request->session()->get('messageType'),
            ],
        ];
    }
}
