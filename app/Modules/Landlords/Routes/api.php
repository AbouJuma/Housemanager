<?php

use App\Modules\Landlords\Http\Controllers\Api\V1\LandlordController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('landlords', LandlordController::class)->names('api.v1.landlords');
});
