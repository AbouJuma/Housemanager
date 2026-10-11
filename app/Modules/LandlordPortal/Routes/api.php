<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\NotificationsController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\AuthController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\DashboardController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\DocumentController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\ExpenseController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\MaintenanceRequestController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\PropertyController;
use Illuminate\Support\Facades\Route;

// No token yet at login time, so this sits outside 'api-tenant'.
Route::post('portal/landlord/login', [AuthController::class, 'login'])->name('api.v1.portal.landlord.login');

Route::prefix('portal/landlord')->name('api.v1.portal.landlord.')->middleware(['api-tenant', 'ensure.landlord-token'])->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // My account (the app's More -> Profile).
    Route::get('profile', [AccountController::class, 'show'])->name('profile');
    Route::put('profile', [AccountController::class, 'update'])->name('profile.update');
    Route::put('password', [AccountController::class, 'password'])->name('password');

    // Notification inbox (the app's bell).
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show');

    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');

    Route::get('maintenance-requests', [MaintenanceRequestController::class, 'index'])->name('maintenance-requests.index');

    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
});
