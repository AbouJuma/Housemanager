<?php

use App\Modules\Maintenance\Http\Controllers\Api\V1\MaintenanceRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('maintenance-requests', MaintenanceRequestController::class)->names('api.v1.maintenance-requests');

    Route::post('maintenance-requests/{maintenance_request}/record-expense', [MaintenanceRequestController::class, 'recordExpense'])
        ->name('api.v1.maintenance-requests.record-expense');

    // Status only - the mobile app's quick update for caretakers/cleaners.
    Route::patch('maintenance-requests/{maintenance_request}/status', [MaintenanceRequestController::class, 'updateStatus'])
        ->name('api.v1.maintenance-requests.status');
});
