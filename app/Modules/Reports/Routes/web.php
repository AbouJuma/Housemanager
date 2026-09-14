<?php

use App\Modules\Reports\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('reports')->name('reports.')->group(function (): void {
    Route::get('/', [ReportController::class, 'index'])->name('index');

    Route::get('rent-roll', [ReportController::class, 'rentRoll'])->name('rent-roll');
    Route::get('rent-roll/export/{format}', [ReportController::class, 'rentRollExport'])->name('rent-roll.export');

    Route::get('occupancy', [ReportController::class, 'occupancy'])->name('occupancy');
    Route::get('occupancy/export/{format}', [ReportController::class, 'occupancyExport'])->name('occupancy.export');

    Route::get('income-statement', [ReportController::class, 'incomeStatement'])->name('income-statement');
    Route::get('income-statement/export/{format}', [ReportController::class, 'incomeStatementExport'])->name('income-statement.export');

    Route::get('subscription-status', [ReportController::class, 'subscriptionStatus'])->name('subscription-status');
    Route::get('billing-status', [ReportController::class, 'billingStatus'])->name('billing-status');

    Route::get('ar-aging', [ReportController::class, 'arAging'])->name('ar-aging');
    Route::get('ar-aging/export/{format}', [ReportController::class, 'arAgingExport'])->name('ar-aging.export');
});
