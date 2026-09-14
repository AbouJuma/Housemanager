<?php

use App\Modules\Companies\Http\Controllers\Api\V1\CompanyController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('companies', CompanyController::class)->names('api.v1.companies');
});
