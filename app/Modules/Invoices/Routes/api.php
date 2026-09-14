<?php

use App\Modules\Invoices\Http\Controllers\Api\V1\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('invoices', InvoiceController::class)->names('api.v1.invoices');

    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('api.v1.invoices.send');
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('api.v1.invoices.cancel');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('api.v1.invoices.payments.store');
});
