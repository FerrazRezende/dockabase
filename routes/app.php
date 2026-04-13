<?php

declare(strict_types=1);

use App\Http\Controllers\App\CredentialController;
use App\Http\Controllers\App\DatabaseController;
use App\Http\Controllers\System\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| App Routes
|--------------------------------------------------------------------------
|
| Routes for authenticated users with RBAC access.
| Databases and Credentials are managed here.
|
*/

Route::middleware(['web', 'auth'])
    ->prefix('app')
    ->name('app.')
    ->group(function (): void {
        // Users (API only)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');

        // Databases - requires database-creator feature flag
        Route::middleware(['feature:database-creator'])->group(function (): void {
            Route::get('/databases', [DatabaseController::class, 'index'])->name('databases.index');
            Route::post('/databases', [DatabaseController::class, 'store'])->name('databases.store');
            Route::get('/databases/create', [DatabaseController::class, 'create'])->name('databases.create');
            Route::get('/databases/{database}', [DatabaseController::class, 'show'])->name('databases.show');
            Route::patch('/databases/{database}', [DatabaseController::class, 'update'])->name('databases.update');
            Route::delete('/databases/{database}', [DatabaseController::class, 'destroy'])->name('databases.destroy');
            Route::post('/databases/{database}/credentials', [DatabaseController::class, 'attachCredential'])->name('databases.credentials.attach');
            Route::delete('/databases/{database}/credentials/{credential}', [DatabaseController::class, 'detachCredential'])->name('databases.credentials.detach');
        });

        // Credentials - requires credentials-manager feature flag
        Route::middleware(['feature:credentials-manager'])->group(function (): void {
            Route::get('/credentials', [CredentialController::class, 'index'])->name('credentials.index');
            Route::post('/credentials', [CredentialController::class, 'store'])->name('credentials.store');
            Route::get('/credentials/create', [CredentialController::class, 'create'])->name('credentials.create');
            Route::get('/credentials/{credential}', [CredentialController::class, 'show'])->name('credentials.show');
            Route::patch('/credentials/{credential}', [CredentialController::class, 'update'])->name('credentials.update');
            Route::delete('/credentials/{credential}', [CredentialController::class, 'destroy'])->name('credentials.destroy');
            Route::post('/credentials/{credential}/users', [CredentialController::class, 'attachUser'])->name('credentials.users.attach');
            Route::delete('/credentials/{credential}/users/{user}', [CredentialController::class, 'detachUser'])->name('credentials.users.detach');
        });
    });
