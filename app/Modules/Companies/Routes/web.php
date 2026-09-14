<?php

use App\Modules\Companies\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('companies', CompanyController::class);
    Route::put('companies/{company}/branding', [CompanyController::class, 'updateBranding'])
        ->name('companies.branding.update');
});
