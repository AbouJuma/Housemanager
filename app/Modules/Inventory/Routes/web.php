<?php

use App\Modules\Inventory\Http\Controllers\InventoryItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::resource('inventory-items', InventoryItemController::class);

    Route::post('inventory-items/{inventory_item}/record-transaction', [InventoryItemController::class, 'recordTransaction'])
        ->name('inventory-items.record-transaction');
});
