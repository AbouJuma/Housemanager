<?php

use App\Modules\Integrations\Http\Controllers\IntegrationController;
use Illuminate\Support\Facades\Route;

$typeConstraint = implode('|', array_keys(config('integrations.types', [])));

Route::middleware(['web', 'auth'])
    ->where(['type' => $typeConstraint])
    ->group(function (): void {
        Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
        Route::get('/integrations/{type}/{provider}/edit', [IntegrationController::class, 'edit'])->name('integrations.edit');
        Route::put('/integrations/{type}/{provider}', [IntegrationController::class, 'update'])->name('integrations.update');
        Route::post('/integrations/{type}/{provider}/test', [IntegrationController::class, 'test'])->name('integrations.test');
    });
