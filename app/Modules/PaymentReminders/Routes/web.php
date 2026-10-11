<?php

use App\Modules\PaymentReminders\Http\Controllers\ReminderRuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::post('reminder-rules/reorder', [ReminderRuleController::class, 'reorder'])->name('reminder-rules.reorder');
    Route::resource('reminder-rules', ReminderRuleController::class)->only(['index', 'store', 'update', 'destroy']);
});
