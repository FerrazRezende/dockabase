<?php

declare(strict_types=1);

use App\Http\Controllers\System\FeatureFlagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Routes
|--------------------------------------------------------------------------
|
| Routes for the system administration panel (/system/*).
| Single-tenant: features are global per instance.
|
*/

Route::middleware(['auth', 'verified'])
    ->prefix('system')
    ->name('system.')
    ->group(function (): void {
        Route::get('/features', [FeatureFlagController::class, 'index'])
            ->name('features.index');
    });
