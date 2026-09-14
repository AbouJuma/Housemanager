<?php

use App\Modules\VisitorManagement\Http\Controllers\Api\V1\VisitorController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('visitors', VisitorController::class)->names('api.v1.visitors');

    Route::post('visitors/{visitor}/check-out', [VisitorController::class, 'checkOut'])
        ->name('api.v1.visitors.check-out');
});
