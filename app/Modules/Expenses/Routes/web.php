<?php

use App\Modules\Expenses\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('expenses', ExpenseController::class)->except(['destroy']);

    Route::post('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.mark-paid');
    Route::post('expenses/{expense}/void', [ExpenseController::class, 'void'])->name('expenses.void');
});
