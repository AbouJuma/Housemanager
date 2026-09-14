<?php

use App\Modules\Landlords\Http\Controllers\LandlordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('landlords', LandlordController::class);
});
