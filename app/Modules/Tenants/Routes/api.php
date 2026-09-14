<?php

use App\Modules\Tenants\Http\Controllers\Api\V1\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('tenants', TenantController::class)->names('api.v1.tenants');
});
