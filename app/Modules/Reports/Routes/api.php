<?php

use App\Modules\Reports\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->prefix('reports')->name('api.v1.reports.')->group(function (): void {
    Route::get('rent-roll', [ReportController::class, 'rentRoll'])->name('rent-roll');
    Route::get('occupancy', [ReportController::class, 'occupancy'])->name('occupancy');
});
