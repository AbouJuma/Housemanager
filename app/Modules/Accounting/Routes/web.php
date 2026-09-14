<?php

use App\Modules\Accounting\Http\Controllers\AccountController;
use App\Modules\Accounting\Http\Controllers\JournalEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('accounts', AccountController::class);
    Route::post('accounts/ajax', [AccountController::class, 'storeAjax'])->name('accounts.store.ajax');

    // Explicit parameter name ('entry', not the auto-derived
    // 'journal_entry') so it matches every controller method's $entry
    // argument for implicit route-model binding.
    Route::resource('journal-entries', JournalEntryController::class)
        ->parameters(['journal-entries' => 'entry'])
        ->only(['index', 'create', 'store', 'show']);
    Route::post('journal-entries/{entry}/void', [JournalEntryController::class, 'void'])->name('journal-entries.void');
});
