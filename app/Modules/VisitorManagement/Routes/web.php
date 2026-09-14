<?php

use App\Modules\VisitorManagement\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('visitors', VisitorController::class);

    Route::post('visitors/{visitor}/check-out', [VisitorController::class, 'checkOut'])
        ->name('visitors.check-out');
});
