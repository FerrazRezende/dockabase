<?php

declare(strict_types=1);

use App\Http\Controllers\System\FeatureFlagController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Routes
|--------------------------------------------------------------------------
|
| Routes for the system administration panel (/system/*).
| Only accessible by God Admin (is_admin = true).
| Features, System Settings, etc.
|
*/

Route::middleware(['web', 'auth'])
    ->prefix('system')
    ->name('system.')
    ->group(function (): void {
        // Features - God Admin only
        Route::get('/features', [FeatureFlagController::class, 'index'])->name('features.index');
        Route::get('/features/{feature}', [FeatureFlagController::class, 'show'])->name('features.show');
        Route::post('/features/{feature}/activate', [FeatureFlagController::class, 'activate'])->name('features.activate');
        Route::post('/features/{feature}/deactivate', [FeatureFlagController::class, 'deactivate'])->name('features.deactivate');
        Route::patch('/features/{feature}', [FeatureFlagController::class, 'update'])->name('features.update');
        Route::get('/features/{feature}/history', [FeatureFlagController::class, 'history'])->name('features.history');
        Route::post('/features/{feature}/users', [FeatureFlagController::class, 'addUser'])->name('features.users.add');
        Route::delete('/features/{feature}/users/{userId}', [FeatureFlagController::class, 'removeUser'])->name('features.users.remove');

        // Users - God Admin only
        Route::get('/users', [UserController::class, 'indexForAdmin'])->name('users.index');
    });
