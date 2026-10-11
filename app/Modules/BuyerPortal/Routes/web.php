<?php

use App\Modules\BuyerPortal\Http\Controllers\BuyerPortalAccessController;
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
