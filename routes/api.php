<?php

declare(strict_types=1);

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\UserStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes for API endpoints (JSON only).
|
*/

Route::middleware(['web', 'auth'])->prefix('api')->group(function (): void {
    // User status endpoints
    Route::get('/user/status', [UserStatusController::class, 'index'])->name('api.user.status');
    Route::post('/user/status', [UserStatusController::class, 'store'])->name('api.user.status.set');
    Route::post('/user/heartbeat', [UserStatusController::class, 'heartbeat'])->name('api.user.heartbeat');
    Route::post('/user/statuses/batch', [UserStatusController::class, 'batch'])->name('api.user.statuses.batch');

    // Notification endpoints
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
