<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Enums\RolloutStrategyEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\ActivateFeatureRequest;
use App\Http\Requests\System\UpdateFeatureRequest;
use App\Http\Resources\FeatureCollection;
use App\Http\Resources\FeatureResource;
use App\Models\User;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeatureFlagController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureService
    ) {}

    /**
     * Display a listing of all features.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $features = $this->featureService->getAllFeatures();

        if ($request->wantsJson()) {
            return new FeatureCollection($features);
        }

        return Inertia::render('System/Features/Index', [
            'features' => (new FeatureCollection($features))->toArray($request),
        ]);
    }

    /**
     * Display a specific feature.
     */
    public function show(Request $request, string $feature)
    {
        abort_unless($request->user()->is_admin, 403);

        $featureDto = $this->featureService->getFeature($feature);

        abort_unless($featureDto, 404, 'Feature not found');

        if ($request->wantsJson()) {
            return new FeatureResource($featureDto);
        }

        $history = $this->featureService->getHistory($feature);
        $users = User::select(['id', 'name', 'email', 'avatar'])
            ->orderBy('name')
            ->get();

        // Calculate which users have access to this feature
        $usersWithAccess = $this->getUsersWithAccess($featureDto, $users);

        return Inertia::render('System/Features/Show', [
            'feature' => (new FeatureResource($featureDto))->toArray($request),
            'history' => $history,
            'users' => $users,
            'usersWithAccess' => $usersWithAccess,
        ]);
    }

    /**
     * Get users who have access to the feature based on strategy.
     */
    private function getUsersWithAccess(array $feature, $allUsers): array
    {
        if (! $feature['is_active']) {
            return [];
        }

        return match (RolloutStrategyEnum::from($feature['strategy'])) {
            RolloutStrategyEnum::All => $allUsers->toArray(),
            RolloutStrategyEnum::Percentage => $allUsers
                ->filter(fn ($user) => $this->checkPercentage((string) $user->id, $feature['percentage']))
                ->values()
                ->toArray(),
            RolloutStrategyEnum::Users => $allUsers
                ->filter(fn ($user) => in_array((string) $user->id, $feature['user_ids'] ?? []))
                ->values()
                ->toArray(),
            default => [],
        };
    }

    /**
     * Deterministic percentage check matching FeatureFlagService.
     */
    private function checkPercentage(string $userId, int $percentage): bool
    {
        $hash = crc32($userId);

        return ($hash % 100) < $percentage;
    }

    /**
     * Activate a feature.
     */
    public function activate(ActivateFeatureRequest $request, string $feature)
    {
        abort_unless($request->user()->is_admin, 403);

        $featureDto = $this->featureService->activate(
            $feature,
            [
                'strategy' => $request->validated('strategy'),
                'percentage' => $request->validated('percentage', 0),
                'user_ids' => $request->validated('user_ids'),
            ],
            $request->user()
        );

        if ($request->wantsJson()) {
            return new FeatureResource($featureDto);
        }

        return redirect()->back();
    }

    /**
     * Deactivate a feature.
     */
    public function deactivate(Request $request, string $feature)
    {
        abort_unless($request->user()->is_admin, 403);

        $featureDto = $this->featureService->deactivate($feature, $request->user());

        if ($request->wantsJson()) {
            return new FeatureResource($featureDto);
        }

        return redirect()->back();
    }

    /**
     * Update feature settings.
     */
    public function update(UpdateFeatureRequest $request, string $feature)
    {
        abort_unless($request->user()->is_admin, 403);

        $featureDto = $this->featureService->update(
            $feature,
            $request->validated(),
            $request->user()
        );

        return new FeatureResource($featureDto);
    }

    /**
     * Get feature history.
     */
    public function history(Request $request, string $feature)
    {
        abort_unless($request->user()->is_admin, 403);

        $history = $this->featureService->getHistory($feature);

        return response()->json(['data' => $history]);
    }

    /**
     * Add a user to the feature's allowlist.
     */
    public function addUser(Request $request, string $feature)
    {
        abort_unless($request->user()->is_admin, 403);

        $request->validate(['user_id' => 'required|string']);

        $featureDto = $this->featureService->addUser(
            $feature,
            $request->input('user_id'),
            $request->user()
        );

        return new FeatureResource($featureDto);
    }

    /**
     * Remove a user from the feature's allowlist.
     */
    public function removeUser(Request $request, string $feature, string $userId)
    {
        abort_unless($request->user()->is_admin, 403);

        $featureDto = $this->featureService->removeUser(
            $feature,
            $userId,
            $request->user()
        );

        return new FeatureResource($featureDto);
    }
}
