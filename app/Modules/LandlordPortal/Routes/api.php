<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\NotificationsController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\AuthController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\BuyerController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\DashboardController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\DocumentController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\ExpenseController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\InvoiceController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\MaintenanceRequestController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\PropertyController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\ReportController;
use App\Modules\LandlordPortal\Http\Controllers\Api\V1\TenantController;
use App\Modules\LandlordPortal\Http\Middleware\EnsureLandlordCan;
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

    // Each needs the permission on the company's "Landlord" role
    // (Settings -> Roles); the app hides what the role doesn't allow.
    $can = fn (string $permission) => EnsureLandlordCan::class.':'.$permission;

    Route::get('property-options', [PropertyController::class, 'options'])->name('properties.options');
    Route::get('properties', [PropertyController::class, 'index'])->name('properties.index')->middleware($can('properties.viewAny'));
    Route::post('properties', [PropertyController::class, 'store'])->name('properties.store')->middleware($can('properties.create'));
    Route::get('properties/{property}', [PropertyController::class, 'show'])->name('properties.show')->middleware($can('properties.viewAny'));
    Route::put('properties/{property}', [PropertyController::class, 'update'])->name('properties.update')->middleware($can('properties.update'));
    Route::post('properties/{property}/buildings', [PropertyController::class, 'storeBuilding'])->name('buildings.store')->middleware($can('units.create'));
    Route::post('buildings/{building}/units', [PropertyController::class, 'storeUnit'])->name('units.store')->middleware($can('units.create'));
    Route::put('units/{unit}', [PropertyController::class, 'updateUnit'])->name('units.update')->middleware($can('units.update'));

    Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index')->middleware($can('tenants.viewAny'));
    Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store')->middleware($can('tenants.create'));
    Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show')->middleware($can('tenants.viewAny'));
    Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update')->middleware($can('tenants.update'));

    Route::get('buyers', [BuyerController::class, 'index'])->name('buyers.index')->middleware($can('buyers.viewAny'));
    Route::get('buyers/{buyer}', [BuyerController::class, 'show'])->name('buyers.show')->middleware($can('buyers.viewAny'));
    Route::put('buyers/{buyer}', [BuyerController::class, 'update'])->name('buyers.update')->middleware($can('buyers.update'));

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index')->middleware($can('invoices.viewAny'));

    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss')->middleware($can('reports.view'));
    Route::get('reports/units', [ReportController::class, 'units'])->name('reports.units')->middleware($can('properties.viewAny'));

    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware($can('expenses.viewAny'));
    Route::get('expense-options', [ExpenseController::class, 'options'])->name('expenses.options')->middleware($can('expenses.create'));
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware($can('expenses.create'));
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update')->middleware($can('expenses.update'));

    Route::get('maintenance-requests', [MaintenanceRequestController::class, 'index'])->name('maintenance-requests.index')->middleware($can('maintenance.viewAny'));

    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index')->middleware($can('documents.viewAny'));
});
