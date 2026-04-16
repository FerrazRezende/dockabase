<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\FeatureFlagService;
use App\Services\UserStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
     * Get translations for the current locale.
     */
    protected function getTranslations(): array
    {
        $langFile = lang_path(App::currentLocale() . '.json');

        if (!file_exists($langFile)) {
            return [];
        }

        $translations = json_decode(file_get_contents($langFile), true);

        return $translations ?? [];
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
        $userPermissions = [];
        $userStatus = null;

        if ($user) {
            $featureService = app(FeatureFlagService::class);
            $activeFeatures = $featureService->getActiveFeaturesForUser($user);

            // Get all permissions for the user (excluding denied)
            // Admins get all permissions since they bypass backend checks
            if ($user->is_admin) {
                $userPermissions = $user->getActualPermissions()->pluck('name')->toArray();
                // If admin has no Spatie role, still grant all permissions for frontend checks
                if (empty($userPermissions)) {
                    $userPermissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
                }
            } else {
                $userPermissions = $user->getActualPermissions()->pluck('name')->toArray();
            }

            // Get user's current status
            $statusService = app(UserStatusService::class);
            $statusWithMeta = $statusService->getStatusWithMetadata($user->id);
            $userStatus = $statusWithMeta ? $statusWithMeta['status'] : 'offline';
        }

        // Build impersonation data
        $impersonating = [
            'is_impersonating' => Session::has('impersonating_id'),
            'original_user_id' => Session::get('original_user_id'),
        ];

        // If impersonating, fetch both original and target user details
        if ($impersonating['is_impersonating'] && $user) {
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
            'userPermissions' => $userPermissions,
            'userStatus' => $userStatus,
            'impersonating' => $impersonating,
            'locale' => App::currentLocale(),
            'translations' => $this->getTranslations(),
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
