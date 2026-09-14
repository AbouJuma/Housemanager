<?php

use App\Modules\RentCollection\Http\Controllers\RentCollectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('invoices/{invoice}/charge', [RentCollectionController::class, 'charge'])->name('invoices.charge');
    Route::post('invoices/{invoice}/payment-attempts/{attempt}/verify', [RentCollectionController::class, 'verify'])->name('invoices.payment-attempts.verify');
});
