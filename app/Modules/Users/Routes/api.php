<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\NotificationsController;
use App\Modules\Users\Http\Controllers\Api\V1\AuthController;
use App\Modules\Users\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

// No token yet at login time, so this sits outside 'api-tenant'.
Route::post('auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

Route::middleware('api-tenant')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
    // My account (the app's More -> Profile): details, permissions, password.
    Route::get('auth/me', [AccountController::class, 'show'])->name('api.v1.auth.me');
    Route::put('auth/profile', [AccountController::class, 'update'])->name('api.v1.auth.profile');
    Route::put('auth/password', [AccountController::class, 'password'])->name('api.v1.auth.password');
    // Notification inbox (the app's bell).
    Route::get('notifications', [NotificationsController::class, 'index'])->name('api.v1.notifications.index');
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('api.v1.notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('api.v1.notifications.read');
    Route::apiResource('users', UserController::class)->names('api.v1.users');
});
