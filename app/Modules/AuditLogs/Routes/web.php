<?php

use App\Modules\AuditLogs\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});
