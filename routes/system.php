<?php

declare(strict_types=1);

use App\Http\Controllers\System\FeatureFlagController;
use App\Http\Controllers\System\ImpersonateController;
use App\Http\Controllers\System\MigrationController;
use App\Http\Controllers\System\PermissionController;
use App\Http\Controllers\System\RoleController;
use App\Http\Controllers\System\UserController;
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

        // Permissions - God Admin only (read-only)
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

        // Roles - God Admin only (API endpoints for internal use)
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');

        // Users - God Admin only
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role.update');
        Route::post('/users/{user}/permissions', [UserController::class, 'syncPermissions'])->name('users.permissions.sync');
        Route::get('/users/{user}/activities', [UserController::class, 'activities'])->name('users.activities');
        Route::post('/users/{user}/impersonate', [ImpersonateController::class, 'start'])->name('users.impersonate.start');

        // Migrations - requires schema-builder feature flag
        Route::middleware(['feature:schema-builder'])->group(function (): void {
            Route::get('/databases/{database}/migrations', [MigrationController::class, 'index'])->name('databases.migrations.index');
            Route::post('/databases/{database}/migrations', [MigrationController::class, 'store'])->name('databases.migrations.store');
            Route::post('/databases/{database}/migrations/{migration}/rollback', [MigrationController::class, 'rollback'])->name('databases.migrations.rollback');
            Route::get('/databases/{database}/migrations/{migration}/sql', [MigrationController::class, 'showSql'])->name('databases.migrations.sql');
        });

        // Impersonate
        Route::post('/stop-impersonating', [ImpersonateController::class, 'stop'])->name('impersonate.stop');
    });
