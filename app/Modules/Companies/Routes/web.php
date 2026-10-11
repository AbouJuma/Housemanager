<?php

use App\Modules\Companies\Http\Controllers\BrandingController;
use App\Modules\Companies\Http\Controllers\CompanyController;
use App\Modules\Companies\Http\Controllers\CompanyProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('companies', CompanyController::class);
    Route::put('companies/{company}/branding', [CompanyController::class, 'updateBranding'])
        ->name('companies.branding.update');

    Route::get('settings/company', [CompanyProfileController::class, 'edit'])->name('company-profile.edit');
    Route::put('settings/company', [CompanyProfileController::class, 'update'])->name('company-profile.update');

    Route::get('settings/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('settings/branding', [BrandingController::class, 'update'])->name('branding.update');
});
