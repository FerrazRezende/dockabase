<?php

use App\Http\Controllers\Api\V1\FeatureController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Public API for end users to check their feature flags.
|
*/

Route::middleware(['auth:sanctum'])
    ->prefix('api/v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
        Route::get('/features/{feature}', [FeatureController::class, 'show'])->name('features.show');
    });
