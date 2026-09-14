<?php

use App\Modules\Users\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('users', UserController::class)->names('api.v1.users');
});
