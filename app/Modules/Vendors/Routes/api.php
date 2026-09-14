<?php

use App\Modules\Vendors\Http\Controllers\Api\V1\VendorController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('vendors', VendorController::class)->names('api.v1.vendors');
});
