<?php

use App\Modules\Leases\Http\Controllers\Api\V1\LeaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('leases', LeaseController::class)->names('api.v1.leases');
});
