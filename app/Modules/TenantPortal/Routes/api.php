<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\NotificationsController;
use App\Modules\TenantPortal\Http\Controllers\Api\V1\AuthController;
use App\Modules\TenantPortal\Http\Controllers\Api\V1\DashboardController;
use App\Modules\TenantPortal\Http\Controllers\Api\V1\DocumentController;
use App\Modules\TenantPortal\Http\Controllers\Api\V1\InvoiceController;
use App\Modules\TenantPortal\Http\Controllers\Api\V1\LeaseController;
use App\Modules\TenantPortal\Http\Controllers\Api\V1\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

// No token yet at login time, so this sits outside 'api-tenant'.
Route::post('portal/tenant/login', [AuthController::class, 'login'])->name('api.v1.portal.tenant.login');

Route::prefix('portal/tenant')->name('api.v1.portal.tenant.')->middleware(['api-tenant', 'ensure.tenant-token'])->group(function (): void {
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

    Route::get('leases', [LeaseController::class, 'index'])->name('leases.index');
    Route::get('leases/{lease}', [LeaseController::class, 'show'])->name('leases.show');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');

    Route::get('maintenance-requests', [MaintenanceRequestController::class, 'index'])->name('maintenance-requests.index');
    Route::get('maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'show'])->name('maintenance-requests.show');
    Route::post('maintenance-requests', [MaintenanceRequestController::class, 'store'])->name('maintenance-requests.store');

    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
});
