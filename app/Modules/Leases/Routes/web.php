<?php

use App\Modules\Leases\Http\Controllers\LeaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('leases', LeaseController::class);
});
