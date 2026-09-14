<?php

use App\Modules\Inventory\Http\Controllers\Api\V1\InventoryItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('inventory-items', InventoryItemController::class)->names('api.v1.inventory-items');

    Route::post('inventory-items/{inventory_item}/record-transaction', [InventoryItemController::class, 'recordTransaction'])
        ->name('api.v1.inventory-items.record-transaction');
});
