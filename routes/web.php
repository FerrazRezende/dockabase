<?php

use App\Http\Controllers\AvatarController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Profile\LocaleController as ProfileLocaleController;
use App\Http\Controllers\Profile\ProfilePhotoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Public locale route for guests
Route::patch('/locale', [LocaleController::class, 'set'])->name('locale.set');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Avatar proxy - serves images from MinIO (auth required)
    Route::get('/avatars/{userId}', [AvatarController::class, 'show'])->name('avatar.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/locale', [ProfileLocaleController::class, 'update'])
        ->name('profile.locale.update')
        ->middleware(['auth', 'verified']);
    Route::post('/profile/photo', [ProfilePhotoController::class, 'store'])
        ->name('profile.photo.store');
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy'])
        ->name('profile.photo.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/app.php';
require __DIR__.'/system.php';
