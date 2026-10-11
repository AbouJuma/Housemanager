<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\NotificationsController;
use App\Modules\BuyerPortal\Http\Controllers\Api\V1\AuthController;
use App\Modules\BuyerPortal\Http\Controllers\Api\V1\DashboardController;
use App\Modules\BuyerPortal\Http\Controllers\Api\V1\InvoiceController;
use App\Modules\BuyerPortal\Http\Controllers\Api\V1\SaleController;
use Illuminate\Support\Facades\Route;

// No token yet at login time, so this sits outside 'api-tenant'.
Route::post('portal/buyer/login', [AuthController::class, 'login'])->name('api.v1.portal.buyer.login');

Route::prefix('portal/buyer')->name('api.v1.portal.buyer.')->middleware(['api-tenant', 'ensure.buyer-token'])->group(function (): void {
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

    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
});
