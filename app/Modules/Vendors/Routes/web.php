<?php

use App\Modules\Vendors\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('vendors', VendorController::class);
});
