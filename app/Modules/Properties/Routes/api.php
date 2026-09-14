<?php

use App\Modules\Properties\Http\Controllers\Api\V1\BuildingController;
use App\Modules\Properties\Http\Controllers\Api\V1\PropertyController;
use App\Modules\Properties\Http\Controllers\Api\V1\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::apiResource('properties', PropertyController::class)->names('api.v1.properties');

    Route::get('properties/{property}/buildings', [BuildingController::class, 'index'])->name('api.v1.buildings.index');
    Route::post('properties/{property}/buildings', [BuildingController::class, 'store'])->name('api.v1.buildings.store');
    Route::get('buildings/{building}', [BuildingController::class, 'show'])->name('api.v1.buildings.show');
    Route::put('buildings/{building}', [BuildingController::class, 'update'])->name('api.v1.buildings.update');
    Route::delete('buildings/{building}', [BuildingController::class, 'destroy'])->name('api.v1.buildings.destroy');

    Route::get('buildings/{building}/units', [UnitController::class, 'index'])->name('api.v1.units.index');
    Route::post('buildings/{building}/units', [UnitController::class, 'store'])->name('api.v1.units.store');
    Route::get('units/{unit}', [UnitController::class, 'show'])->name('api.v1.units.show');
    Route::put('units/{unit}', [UnitController::class, 'update'])->name('api.v1.units.update');
    Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('api.v1.units.destroy');
});
