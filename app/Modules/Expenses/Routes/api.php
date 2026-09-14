<?php

use App\Modules\Expenses\Http\Controllers\Api\V1\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('expenses', ExpenseController::class)->except(['destroy'])->names('api.v1.expenses');

    Route::post('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('api.v1.expenses.mark-paid');
    Route::post('expenses/{expense}/void', [ExpenseController::class, 'void'])->name('api.v1.expenses.void');
});
