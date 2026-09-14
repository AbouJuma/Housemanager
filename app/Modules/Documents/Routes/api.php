<?php

use App\Modules\Documents\Http\Controllers\Api\V1\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('documents', DocumentController::class)->names('api.v1.documents');
});
