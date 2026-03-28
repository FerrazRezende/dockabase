<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FeatureFlagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureService
    ) {}

    /**
     * Get features active for the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated');

        $features = $this->featureService->getActiveFeaturesForUser($user);

        return response()->json([
            'features' => $features,
        ]);
    }

    /**
     * Check if a specific feature is active for the current user.
     */
    public function show(Request $request, string $feature): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated');

        $isActive = $this->featureService->isActiveForUser($feature, $user);

        return response()->json([
            'feature' => $feature,
            'is_active' => $isActive,
        ]);
    }
}
