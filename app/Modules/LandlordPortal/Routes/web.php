<?php

use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalAccessController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalAuthController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalDashboardController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalDocumentController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalExpenseController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalMaintenanceRequestController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalPropertyController;
use Illuminate\Support\Facades\Route;

// Staff-side: managing a landlord's own portal login, gated by the
// existing LandlordPolicy 'update' ability - the same pattern as
// Module 26's tenants.portal-access.* routes.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('landlords/{landlord}/portal-access/enable', [LandlordPortalAccessController::class, 'enable'])
        ->name('landlords.portal-access.enable');
    Route::post('landlords/{landlord}/portal-access/disable', [LandlordPortalAccessController::class, 'disable'])
        ->name('landlords.portal-access.disable');
});

// Portal-side: the landlord's own read-only self-service area, entirely
// under the 'landlord' guard - a third identity, independent of both
// staff and Module 26's tenant guard. See ARCHITECTURE.md §20.
Route::prefix('landlord-portal')->name('landlord-portal.')->middleware('web')->group(function (): void {
    Route::get('login', [LandlordPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [LandlordPortalAuthController::class, 'login']);
    Route::post('logout', [LandlordPortalAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:landlord')->group(function (): void {
        Route::get('dashboard', [LandlordPortalDashboardController::class, 'index'])->name('dashboard');

        Route::get('properties', [LandlordPortalPropertyController::class, 'index'])->name('properties.index');
        Route::get('properties/{property}', [LandlordPortalPropertyController::class, 'show'])->name('properties.show');

        Route::get('expenses', [LandlordPortalExpenseController::class, 'index'])->name('expenses.index');

        Route::get('maintenance-requests', [LandlordPortalMaintenanceRequestController::class, 'index'])->name('maintenance-requests.index');

        Route::get('documents', [LandlordPortalDocumentController::class, 'index'])->name('documents.index');
    });
});
