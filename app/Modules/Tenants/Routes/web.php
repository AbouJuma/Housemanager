<?php

use App\Modules\Tenants\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('tenants', TenantController::class);
    Route::post('tenants/ajax', [TenantController::class, 'storeAjax'])->name('tenants.store.ajax');
    Route::get('tenants-lookup', [TenantController::class, 'search'])->name('tenants.search');
});
