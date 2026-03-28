<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureCollection;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;

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
        abort_unless($request->user()->hasRole('admin'), 403);

        $features = $this->featureService->getAllFeatures();

        return new FeatureCollection($features);
    }
}
