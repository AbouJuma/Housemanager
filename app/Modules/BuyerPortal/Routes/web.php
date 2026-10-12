<?php

use App\Modules\BuyerPortal\Http\Controllers\BuyerPortalAccessController;
use App\Modules\BuyerPortal\Http\Controllers\BuyerPortalAuthController;
use App\Modules\BuyerPortal\Http\Controllers\BuyerPortalController;
use Illuminate\Support\Facades\Route;

// Staff-side: managing a buyer's own portal login, gated by the
// existing BuyerPolicy 'update' ability (not a route the buyer ever
// sees) - the same 'web'/'auth' guard every other admin screen uses.
// Mirrors App\Modules\TenantPortal\Routes\web.php's staff-side group.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('buyers/{buyer}/portal-access/enable', [BuyerPortalAccessController::class, 'enable'])
        ->name('buyers.portal-access.enable');
    Route::post('buyers/{buyer}/portal-access/disable', [BuyerPortalAccessController::class, 'disable'])
        ->name('buyers.portal-access.disable');
});

// Buyer-side: the buyer's own web portal under the 'buyer' guard - the
// same purchases, instalments and invoices the mobile app shows.
Route::prefix('buyer-portal')->name('buyer-portal.')->middleware('web')->group(function (): void {
    Route::get('login', [BuyerPortalAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [BuyerPortalAuthController::class, 'login']);
    Route::post('logout', [BuyerPortalAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:buyer')->group(function (): void {
        Route::get('dashboard', [BuyerPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('purchases', [BuyerPortalController::class, 'purchases'])->name('purchases.index');
        Route::get('purchases/{sale}', [BuyerPortalController::class, 'purchase'])->name('purchases.show');
        Route::get('invoices', [BuyerPortalController::class, 'invoices'])->name('invoices.index');
        Route::get('invoices/{invoice}', [BuyerPortalController::class, 'invoice'])->name('invoices.show');
    });
});
