<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class FeatureController extends Controller
{
    /**
     * Get features active for the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated');

        $featureNames = array_keys(config('features.definitions', []));
        $features = [];

        foreach ($featureNames as $name) {
            if (Feature::for($user)->active($name)) {
                $features[] = $name;
            }
        }

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

        return response()->json([
            'feature' => $feature,
            'is_active' => Feature::for($user)->active($feature),
        ]);
    }
}
