<?php

use App\Modules\Accounting\Http\Controllers\Api\V1\AccountController;
use App\Modules\Accounting\Http\Controllers\Api\V1\JournalEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('accounts', AccountController::class)->names('api.v1.accounts');

    Route::apiResource('journal-entries', JournalEntryController::class)
        ->parameters(['journal-entries' => 'entry'])
        ->only(['index', 'store', 'show'])
        ->names('api.v1.journal-entries');
    Route::post('journal-entries/{entry}/void', [JournalEntryController::class, 'void'])->name('api.v1.journal-entries.void');
});
