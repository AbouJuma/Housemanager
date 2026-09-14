<?php

use App\Modules\Sales\Http\Controllers\BuyerController;
use App\Modules\Sales\Http\Controllers\InstallmentController;
use App\Modules\Sales\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('sales', SaleController::class);
    Route::resource('buyers', BuyerController::class);
    Route::get('buyers-lookup', [BuyerController::class, 'search'])->name('buyers.search');

    // Installment routes
    Route::get('sales/{sale}/installments/{installment}', [InstallmentController::class, 'show'])
        ->where('installment', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->name('sales.installments.show');
    Route::post('sales/{sale}/installments/{installment}/payment', [InstallmentController::class, 'recordPayment'])
        ->where('installment', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->name('sales.installments.payment');
    Route::post('sales/{sale}/installments/{installment}/overdue', [InstallmentController::class, 'markOverdue'])
        ->where('installment', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
        ->name('sales.installments.overdue');
});
