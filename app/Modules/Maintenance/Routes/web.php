<?php

use App\Modules\Maintenance\Http\Controllers\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('maintenance-requests', MaintenanceRequestController::class);

    Route::post('maintenance-requests/{maintenance_request}/record-expense', [MaintenanceRequestController::class, 'recordExpense'])
        ->name('maintenance-requests.record-expense');
});
