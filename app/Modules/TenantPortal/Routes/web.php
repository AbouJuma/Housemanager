<?php

use App\Modules\TenantPortal\Http\Controllers\PortalAccessController;
use App\Modules\TenantPortal\Http\Controllers\PortalAuthController;
use App\Modules\TenantPortal\Http\Controllers\PortalDashboardController;
use App\Modules\TenantPortal\Http\Controllers\PortalDocumentController;
use App\Modules\TenantPortal\Http\Controllers\PortalInvoiceController;
use App\Modules\TenantPortal\Http\Controllers\PortalLeaseController;
use App\Modules\TenantPortal\Http\Controllers\PortalMaintenanceRequestController;
use Illuminate\Support\Facades\Route;

// Staff-side: managing a tenant's own portal login, gated by the
// existing TenantPolicy 'update' ability (not a route the tenant ever
// sees) - the same 'web'/'auth' guard every other admin screen uses.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('tenants/{tenant}/portal-access/enable', [PortalAccessController::class, 'enable'])
        ->name('tenants.portal-access.enable');
    Route::post('tenants/{tenant}/portal-access/disable', [PortalAccessController::class, 'disable'])
        ->name('tenants.portal-access.disable');
});

// Portal-side: the tenant's own self-service area, entirely under the
// 'tenant' guard - a wholly separate login from staff. See
// ARCHITECTURE.md §19.
Route::prefix('portal')->name('portal.')->middleware('web')->group(function (): void {
    Route::get('login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [PortalAuthController::class, 'login']);
    Route::post('logout', [PortalAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:tenant')->group(function (): void {
        Route::get('dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');

        Route::get('leases', [PortalLeaseController::class, 'index'])->name('leases.index');
        Route::get('leases/{lease}', [PortalLeaseController::class, 'show'])->name('leases.show');

        Route::get('invoices', [PortalInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/{invoice}/pay', [PortalInvoiceController::class, 'pay'])->name('invoices.pay');

        Route::get('maintenance-requests', [PortalMaintenanceRequestController::class, 'index'])->name('maintenance-requests.index');
        Route::get('maintenance-requests/create', [PortalMaintenanceRequestController::class, 'create'])->name('maintenance-requests.create');
        Route::post('maintenance-requests', [PortalMaintenanceRequestController::class, 'store'])->name('maintenance-requests.store');

        Route::get('documents', [PortalDocumentController::class, 'index'])->name('documents.index');
    });
});
