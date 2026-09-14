<?php

use App\Modules\Settings\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/settings/system', [SettingsController::class, 'systemEdit'])->name('settings.system.edit');
    Route::put('/settings/system', [SettingsController::class, 'systemUpdate'])->name('settings.system.update');
});
