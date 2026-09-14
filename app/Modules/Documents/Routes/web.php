<?php

use App\Modules\Documents\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('documents', DocumentController::class);
});
