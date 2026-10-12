<?php

use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalAccessController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalAuthController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalDashboardController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalDocumentController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalExpenseController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalMaintenanceRequestController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalPropertyController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalReportController;
use App\Modules\LandlordPortal\Http\Controllers\LandlordPortalTenantController;
use App\Modules\LandlordPortal\Http\Middleware\EnsureLandlordCan;
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

    // Each page needs the matching permission on the company's
    // "Landlord" role (Settings -> Roles), which admins can change.
    Route::middleware('auth:landlord')->group(function (): void {
        $can = fn (string $permission) => EnsureLandlordCan::class.':'.$permission;

        Route::get('dashboard', [LandlordPortalDashboardController::class, 'index'])->name('dashboard');

        Route::get('properties', [LandlordPortalPropertyController::class, 'index'])->name('properties.index');
        Route::get('properties/create', [LandlordPortalPropertyController::class, 'create'])->name('properties.create');
        Route::post('properties', [LandlordPortalPropertyController::class, 'store'])->name('properties.store');
        Route::get('properties/{property}', [LandlordPortalPropertyController::class, 'show'])->name('properties.show');
        Route::get('properties/{property}/edit', [LandlordPortalPropertyController::class, 'edit'])->name('properties.edit');
        Route::put('properties/{property}', [LandlordPortalPropertyController::class, 'update'])->name('properties.update');

        Route::get('tenants', [LandlordPortalTenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/create', [LandlordPortalTenantController::class, 'create'])->name('tenants.create');
        Route::post('tenants', [LandlordPortalTenantController::class, 'store'])->name('tenants.store');

        Route::get('reports/profit-loss', [LandlordPortalReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('reports/units', [LandlordPortalReportController::class, 'units'])->name('reports.units');

        Route::get('expenses', [LandlordPortalExpenseController::class, 'index'])->name('expenses.index')->middleware($can('expenses.viewAny'));

        Route::get('maintenance-requests', [LandlordPortalMaintenanceRequestController::class, 'index'])->name('maintenance-requests.index')->middleware($can('maintenance.viewAny'));

        Route::get('documents', [LandlordPortalDocumentController::class, 'index'])->name('documents.index')->middleware($can('documents.viewAny'));
    });
});
